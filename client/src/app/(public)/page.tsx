"use client";

import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/AuthContext";
import { ArrowRight, Lock, Server, Zap } from "lucide-react";
import Link from "next/link";

const FEATURES = [
  {
    icon: Lock,
    title: "Auth out of the box",
    body: "Signup, login, password reset, and Sanctum cookie sessions wired end-to-end.",
  },
  {
    icon: Server,
    title: "Laravel API",
    body: "A typed Next.js client talks to a battle-tested Laravel backend over JSON.",
  },
  {
    icon: Zap,
    title: "CI/CD ready",
    body: "GitHub Actions workflows ship the frontend and API on every push to main.",
  },
];

export default function HomePage() {
  const { user, isLoading } = useAuth();

  return (
    <div>
      <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-32">
        <div className="mx-auto max-w-3xl text-center">
          <h1 className="text-4xl font-bold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
            Next + Laravel
          </h1>
          <p className="mt-6 text-base text-muted-foreground sm:text-lg">
            A production-ready foundation for your next app — typed Next.js frontend, Laravel API,
            cookie-based auth, and a deploy pipeline that just works.
          </p>
          <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
            {isLoading ? null : user ? (
              <Button size="lg" asChild>
                <Link href="/dashboard">
                  Go to dashboard
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </Button>
            ) : (
              <>
                <Button size="lg" asChild>
                  <Link href="/signup">
                    Get started
                    <ArrowRight className="h-4 w-4" />
                  </Link>
                </Button>
                <Button size="lg" variant="outline" asChild>
                  <Link href="/login">Sign in</Link>
                </Button>
              </>
            )}
          </div>
        </div>
      </section>

      <section className="border-t bg-background">
        <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
          <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            {FEATURES.map(({ icon: Icon, title, body }) => (
              <div key={title} className="rounded-xl border bg-card p-6 shadow-sm">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <Icon className="h-5 w-5" />
                </div>
                <h3 className="mt-4 text-lg font-semibold">{title}</h3>
                <p className="mt-2 text-sm text-muted-foreground">{body}</p>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}
