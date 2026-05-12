"use client";

import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAuth } from "@/context/AuthContext";
import { Briefcase, PenSquare } from "lucide-react";
import Link from "next/link";

const SECTIONS = [
  {
    href: "/my/portfolio",
    title: "Manage Portfolio",
    description: "Build and manage your portfolio.",
    Icon: Briefcase,
  },
  {
    href: "/my/blog",
    title: "Manage Blogs",
    description: "Write and manage your blog posts.",
    Icon: PenSquare,
  },
];

export default function DashboardPage() {
  const { user } = useAuth();
  if (!user) return null;

  return (
    <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Welcome, {user.name}</h1>
        <p className="mt-1 text-sm text-muted-foreground">What would you like to work on today?</p>
      </div>

      <div className="grid gap-6 sm:grid-cols-2">
        {SECTIONS.map(({ href, title, description, Icon }) => (
          <Link
            key={href}
            href={href}
            className="group rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <Card className="h-full transition-all group-hover:border-foreground/20 group-hover:shadow-md">
              <CardHeader>
                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-accent text-accent-foreground">
                  <Icon className="h-5 w-5" />
                </div>
                <CardTitle className="text-xl">{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
              </CardHeader>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
