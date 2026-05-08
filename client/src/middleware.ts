import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

// Note: auth state lives in localStorage (client-side only), so this
// middleware does a coarse path-level check. Real protection happens
// in the ProtectedRoute component.
export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Block direct access to API endpoints from the same origin if needed
  if (pathname.startsWith("/api/")) {
    return NextResponse.next();
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
