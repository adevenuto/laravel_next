"use client";

import { Header } from "@/components/Header";
import { ProtectedRoute } from "@/components/ProtectedRoute";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAuth } from "@/context/AuthContext";

export default function DashboardPage() {
  return (
    <ProtectedRoute>
      <DashboardContent />
    </ProtectedRoute>
  );
}

function DashboardContent() {
  const { user } = useAuth();
  if (!user) return null;

  return (
    <div className="min-h-screen bg-muted/40">
      <Header />
      <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div className="grid gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-2xl sm:text-3xl">Welcome, {user.name}!!</CardTitle>
              <CardDescription>You are signed in as {user.email}.</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">
                This is your dashboard. Use this page as a starting point for building out your app.
              </p>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
}
