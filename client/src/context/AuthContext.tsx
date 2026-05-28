"use client";

import { api, type User } from "@/lib/api";
import { createContext, useCallback, useContext, useEffect, useState } from "react";

type AuthContextType = {
  user: User | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (
    first_name: string,
    last_name: string,
    email: string,
    password: string,
    password_confirmation: string
  ) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // On mount, ask the server who we are. The session cookie (if any) is
  // sent automatically because every fetch uses credentials: "include".
  useEffect(() => {
    api
      .getUser()
      .then((u) => setUser(u))
      .catch(() => setUser(null))
      .finally(() => setIsLoading(false));
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const { user } = await api.login({ email, password });
    setUser(user);
  }, []);

  // Register auto-logs the user in. The session cookie is set server-side via
  // Auth::login + session regenerate; we just seed the user state from the response.
  const register = useCallback(
    async (
      first_name: string,
      last_name: string,
      email: string,
      password: string,
      password_confirmation: string
    ) => {
      const { user } = await api.register({ first_name, last_name, email, password, password_confirmation });
      setUser(user);
    },
    []
  );

  const logout = useCallback(async () => {
    await api.logout().catch(() => {});
    api.resetCsrf();
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, isLoading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
