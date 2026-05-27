const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";

type ApiOptions = {
  method?: string;
  body?: Record<string, unknown>;
};

function getCookie(name: string): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : null;
}

let csrfFetched = false;

async function ensureCsrfCookie(): Promise<void> {
  if (csrfFetched) return;
  const res = await fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: "include",
  });
  if (!res.ok) throw new Error("Could not initialize session");
  csrfFetched = true;
}

async function apiFetch<T>(endpoint: string, options: ApiOptions = {}): Promise<T> {
  const { method = "GET", body } = options;
  const isMutation = method !== "GET" && method !== "HEAD";

  if (isMutation) {
    await ensureCsrfCookie();
  }

  const headers: Record<string, string> = {
    Accept: "application/json",
  };
  if (body !== undefined) {
    headers["Content-Type"] = "application/json";
  }

  const xsrf = getCookie("XSRF-TOKEN");
  if (xsrf && isMutation) {
    headers["X-XSRF-TOKEN"] = xsrf;
  }

  const res = await fetch(`${API_URL}${endpoint}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
    credentials: "include",
  });

  if (res.status === 204) {
    return undefined as T;
  }

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    let message = "An error occurred";
    if (data?.errors) {
      message = Object.values(data.errors as Record<string, string[]>).flat().join(" ");
    } else if (data?.message) {
      message = data.message as string;
    }
    throw new Error(message);
  }

  return data as T;
}

export type User = { id: number; first_name: string; last_name: string; email: string };

export const api = {
  register: (data: {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) => apiFetch<{ message: string }>("/api/register", { method: "POST", body: data }),

  login: (data: { email: string; password: string }) =>
    apiFetch<{ user: User }>("/api/login", { method: "POST", body: data }),

  logout: () => apiFetch<{ message: string }>("/api/logout", { method: "POST" }),

  passwordReset: (data: { email: string }) =>
    apiFetch<{ message: string }>("/api/password-reset", { method: "POST", body: data }),

  passwordResetConfirm: (data: {
    email: string;
    token: string;
    password: string;
    password_confirmation: string;
  }) => apiFetch<{ message: string }>("/api/password-reset/confirm", { method: "POST", body: data }),

  getUser: () => apiFetch<User>("/api/user"),

  // Force re-fetch the CSRF cookie (e.g. on logout when session is invalidated).
  resetCsrf: () => {
    csrfFetched = false;
  },
};
