"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export default function BlogPage() {
  return (
    <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl sm:text-3xl">Your Blog</CardTitle>
          <CardDescription>Write posts and manage your blog.</CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            Coming soon — this is where you&apos;ll draft, edit, and publish blog posts.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
