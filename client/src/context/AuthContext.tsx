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

  // Register no longer auto-logs in — the user must visit /login afterwards.
  // This keeps the response identical whether or not the email is already registered.
  const register = useCallback(
    async (
      first_name: string,
      last_name: string,
      email: string,
      password: string,
      password_confirmation: string
    ) => {
      await api.register({ first_name, last_name, email, password, password_confirmation });
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
