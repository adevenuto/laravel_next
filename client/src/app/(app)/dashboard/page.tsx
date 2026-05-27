"use client";

import { useAuth } from "@/context/AuthContext";

export default function DashboardPage() {
  const { user } = useAuth();
  if (!user) return null;

  return (
    <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
        Welcome, {user.first_name}
      </h1>
      <p className="mt-1 text-sm text-muted-foreground">
        You&apos;re signed in.
      </p>
    </div>
  );
}
