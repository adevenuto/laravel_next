"use client";

import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { useAuth } from "@/context/AuthContext";
import { cn } from "@/lib/utils";
import { LogOut, Menu } from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState } from "react";

const NAV_LINKS: { href: string; label: string }[] = [
  { href: "/dashboard", label: "Dashboard" },
  { href: "/my/portfolio", label: "Portfolio" },
  { href: "/my/blog", label: "Blog" },
];

export function Header() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);

  const showSignInCta = pathname !== "/login";
  const showSignupCta = pathname !== "/signup";

  const handleLogout = async () => {
    setMobileOpen(false);
    await logout();
    router.push("/login");
  };

  return (
    <header className="sticky top-0 z-40 border-b bg-background">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <Link href="/" className="text-lg font-semibold">
          Next + Laravel
        </Link>

        {user && (
          <nav className="hidden flex-1 items-center gap-1 md:flex">
            {NAV_LINKS.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={cn(
                  "rounded-md px-3 py-1.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground",
                  pathname === link.href && "bg-accent text-accent-foreground"
                )}
              >
                {link.label}
              </Link>
            ))}
          </nav>
        )}

        <div className="hidden items-center gap-3 md:flex">
          {user ? (
            <>
              <span className="text-sm text-muted-foreground">{user.email}</span>
              <Button variant="outline" size="sm" onClick={handleLogout}>
                <LogOut className="h-4 w-4" />
                Logout
              </Button>
            </>
          ) : (
            <>
              {showSignInCta && (
                <Button variant="ghost" size="sm" asChild>
                  <Link href="/login">Sign in</Link>
                </Button>
              )}
              {showSignupCta && (
                <Button size="sm" asChild>
                  <Link href="/signup">Get started</Link>
                </Button>
              )}
            </>
          )}
        </div>

        <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
          <SheetTrigger asChild>
            <Button variant="ghost" size="icon" className="md:hidden" aria-label="Open menu">
              <Menu className="h-5 w-5" />
            </Button>
          </SheetTrigger>
          <SheetContent side="right" className="flex w-72 flex-col">
            <SheetTitle className="text-left">Menu</SheetTitle>

            {user ? (
              <>
                <nav className="mt-4 flex flex-col gap-1">
                  {NAV_LINKS.map((link) => (
                    <Link
                      key={link.href}
                      href={link.href}
                      onClick={() => setMobileOpen(false)}
                      className={cn(
                        "rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground",
                        pathname === link.href && "bg-accent text-accent-foreground"
                      )}
                    >
                      {link.label}
                    </Link>
                  ))}
                </nav>
                <div className="mt-auto space-y-3 border-t pt-4">
                  <p className="px-3 text-sm text-muted-foreground">{user.email}</p>
                  <Button variant="outline" className="w-full" onClick={handleLogout}>
                    <LogOut className="h-4 w-4" />
                    Logout
                  </Button>
                </div>
              </>
            ) : (
              <div className="mt-4 flex flex-col gap-2">
                {showSignInCta && (
                  <Button variant="outline" className="w-full" asChild onClick={() => setMobileOpen(false)}>
                    <Link href="/login">Sign in</Link>
                  </Button>
                )}
                {showSignupCta && (
                  <Button className="w-full" asChild onClick={() => setMobileOpen(false)}>
                    <Link href="/signup">Get started</Link>
                  </Button>
                )}
              </div>
            )}
          </SheetContent>
        </Sheet>
      </div>
    </header>
  );
}
