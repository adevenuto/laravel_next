# Authentication & Token Flow

This project uses **Laravel Sanctum in SPA mode**: the Next.js client authenticates via **HttpOnly session cookies + CSRF**, not Bearer tokens. Bearer tokens still work for non-SPA clients (tests, mobile, CLI) — `auth:sanctum` accepts both.

---

## High-level architecture

```
┌──────────────────────────────────┐         ┌────────────────────────────────────────┐
│  Next.js client (port 3000)      │         │  Laravel API (port 8000)               │
│                                  │         │                                        │
│  AuthContext ─► api.getUser()    │         │  routes/api.php                        │
│   on mount                       │         │   ├─ throttle:5,1                      │
│  api.ts ──► fetch w/ cookies +   │ cookies │   │    register / login                 │
│   X-XSRF-TOKEN (mutations)       ├─────────┼─►   │    password-reset (+ confirm)     │
│  /sanctum/csrf-cookie once       │         │   └─ auth:sanctum                      │
│                                  │         │   EnsureFrontendRequestsAreStateful    │
│  Route groups:                   │         │      → StartSession + ValidateCsrf     │
│   (public) / (auth) / (app)      │         │   HasApiTokens for Bearer fallback     │
└──────────────────────────────────┘         └────────────────────────────────────────┘
```

### Key files

| Layer   | File                                                                  | Role                                          |
| ------- | --------------------------------------------------------------------- | --------------------------------------------- |
| Client  | `client/src/lib/api.ts`                                               | Cookie-aware fetch; CSRF helper               |
| Client  | `client/src/context/AuthContext.tsx`                                  | Hydrates `user` from `/api/user` on mount     |
| Client  | `client/src/components/ProtectedRoute.tsx`                            | Used by `(app)/layout.tsx`; redirects if !auth |
| Client  | `client/src/components/Header.tsx`                                    | Adapts CTAs by `user` + `pathname`            |
| Client  | `client/src/components/MainLayout.tsx`                                | Shared shell: Header + main + Footer          |
| Client  | `client/src/app/(public|auth|app)/layout.tsx`                         | Per-group chrome (see §8)                     |
| Backend | `backend/bootstrap/app.php`                                           | `statefulApi()` enables SPA mode              |
| Backend | `backend/config/cors.php`                                             | `supports_credentials: true`; env origins     |
| Backend | `backend/config/sanctum.php`                                          | Expiration + stateful domains                 |
| Backend | `backend/routes/api.php`                                              | `throttle:5,1` on auth + `auth:sanctum` group |
| Backend | `backend/app/Http/Controllers/Auth/*.php`                             | Auth + password-reset controllers             |
| Backend | `backend/app/Notifications/*Notification.php`                         | Welcome + duplicate-signup mailers            |
| Backend | `backend/app/Providers/AppServiceProvider.php`                        | Custom `ResetPassword` URL → frontend         |

### Wire-level cookies & headers

| Thing                   | Set by               | Read by                 | Notes                                                |
| ----------------------- | -------------------- | ----------------------- | ---------------------------------------------------- |
| `laravel_session`       | Laravel              | Laravel                 | **HttpOnly** — invisible to JS                       |
| `XSRF-TOKEN` cookie     | Laravel              | JS (`document.cookie`)  | Not HttpOnly — JS echoes it as a header              |
| `X-XSRF-TOKEN` header   | `api.ts` (mutations) | Laravel                 | Must match the cookie                                |
| `Authorization: Bearer` | Bearer clients only  | Laravel                 | Sanctum fallback when no session                     |

---

## 1. Register flow

```
Browser              Next.js (AuthContext)               Laravel
   │                       │                                    │
   │ submit form           │                                    │
   ├──────────────────────►│                                    │
   │                       │ GET /sanctum/csrf-cookie (once)    │
   │                       ├───────────────────────────────────►│
   │                       │ Set-Cookie: XSRF + session         │
   │                       │◄───────────────────────────────────┤
   │                       │ POST /api/register                 │
   │                       │ X-XSRF-TOKEN: <cookie value>       │
   │                       ├───────────────────────────────────►│
   │                       │                                    │ validate (no unique:users)
   │                       │                                    │ existing = User::first(...)
   │                       │                                    │ if !existing:
   │                       │                                    │   User::create() + WelcomeNotification
   │                       │                                    │ else:
   │                       │                                    │   Hash::make()  ← timing parity
   │                       │                                    │   DuplicateRegistrationNotification
   │                       │ 200 {message}  ◄── identical body  │
   │                       │◄───────────────────────────────────┤
   │ router.push('/login?registered=1')                         │
   │◄──────────────────────┤
```

- **No enumeration**: response is identical whether the email is new or taken.
- **No auto-login**: client redirects to `/login`, which shows a success banner via `?registered=1`.
- **Side effects** (invisible to requester): new user → `WelcomeNotification`; existing user → `DuplicateRegistrationNotification` with a reset-password CTA.
- Dev: `MAIL_MAILER=log` writes both to `backend/storage/logs/laravel.log`.

---

## 2. Login flow

```
Browser           Next.js                          Laravel
   │                  │                                    │
   │ email + password │                                    │
   ├─────────────────►│                                    │
   │                  │ POST /api/login + cookies + CSRF   │
   │                  ├───────────────────────────────────►│
   │                  │                                    │ user = User::first(by email)
   │                  │                                    │ user ? Hash::check : Hash::make ← timing
   │                  │                                    │ on fail   → 422 generic message
   │                  │                                    │ on success → Auth::login + session regen
   │                  │ 200 {user}  or  422 {errors}       │
   │                  │◄───────────────────────────────────┤
   │ /dashboard       │                                    │
   │◄─────────────────┤
```

- Uniform timing: `Hash::make` runs on the missing-user path so attackers can't time-fingerprint email existence.
- Both failure modes (no user, wrong password) return the same 422 with a single `email` error reading "These credentials do not match our records."
- All four public auth routes (`register`, `login`, `password-reset`, `password-reset/confirm`) are rate-limited via `throttle:5,1`.

---

## 3. Authenticated request flow

The pattern for every protected route, present or future:

```
Browser         Next.js (api.ts)                  Laravel
   │                  │                                    │
   ├─────────────────►│                                    │
   │                  │ fetch(`${API}/api/things/42`, {     │
   │                  │   credentials: 'include',           │
   │                  │   /* X-XSRF-TOKEN on mutations */   │
   │                  │ })                                  │
   │                  ├───────────────────────────────────►│
   │                  │                                    │ ① CORS preflight (OPTIONS) → 204
   │                  │                                    │ ② HandleCors echoes Origin
   │                  │                                    │ ③ EnsureFrontendRequestsAreStateful
   │                  │                                    │    → adds session/cookie/CSRF middleware
   │                  │                                    │      for stateful origins
   │                  │                                    │ ④ StartSession + ValidateCsrfToken
   │                  │                                    │ ⑤ auth:sanctum: session OR Bearer OR 401
   │                  │                                    │ ⑥ Controller runs, JSON returned
   │                  │ 200 {data}                         │
   │                  │◄───────────────────────────────────┤
```

- CSRF is enforced on **mutations only** (POST/PUT/PATCH/DELETE).
- `credentials: 'include'` is required cross-origin and pairs with `supports_credentials: true`.
- `auth:sanctum` is dual-mode: cookie first, Bearer fallback — controllers don't need to know which.

---

## 4. Logout flow

```
Browser        Next.js                           Laravel
   │              │                                    │
   ├─────────────►│ POST /api/logout + cookies + CSRF  │
   │              ├───────────────────────────────────►│
   │              │                                    │ token = $request->user()?->currentAccessToken()
   │              │                                    │ Auth::guard('web')->logout()
   │              │                                    │ session()->invalidate() + regenerateToken()
   │              │                                    │ $token?->delete()  ← Bearer revoke
   │              │ 200 {message}                      │
   │              │◄───────────────────────────────────┤
   │              │ setUser(null)
   │ /login       │
   │◄─────────────┤
```

Ends the SPA session AND revokes the personal access token (if the request used one). Other devices stay logged in.

---

## 5. Password reset

Two steps.

**Request a link** — `POST /api/password-reset`. Always returns `200` with a generic message (no enumeration). The email links to:

```
{FRONTEND_URL}/reset-password?token=<token>&email=<email>
```

In dev the link lands in `backend/storage/logs/laravel.log`.

**Confirm with new password** — `POST /api/password-reset/confirm` accepts `{email, token, password, password_confirmation}`, calls `Password::reset()` (validates single-use/time-limited token, updates hash, rotates `remember_token`). 200 on success, 422 on invalid/expired token.

The frontend page (`client/src/app/(auth)/reset-password/page.tsx`) reads `token`+`email` from the query, posts the new password, and redirects to `/login?reset=1` on success.

---

## 6. Page reload / session restoration

No localStorage. On mount, `AuthProvider` calls `GET /api/user`; the browser attaches the session cookie automatically.

- 200 → `setUser(user)`
- 401 → `setUser(null)`
- `ProtectedRoute` (used only by `(app)` pages) waits for hydration, then redirects to `/login` if no user.

Trade-off: HttpOnly token means JS can't exfiltrate it, but the frontend and API must share a parent domain in production for cookies to ride along.

---

## 7. Adding a new protected resource endpoint

### Backend

1. Add the route under the Sanctum-guarded group in `backend/routes/api.php`:
   ```php
   Route::middleware('auth:sanctum')->group(function () {
       Route::get('/projects', [ProjectController::class, 'index']);
   });
   ```
2. Read the user via `$request->user()`. Authorize with a Policy if needed.
3. Test with `actingAs`:
   ```php
   $this->actingAs($user)->getJson('/api/projects')->assertOk();
   ```

### Client

1. Add a method to `client/src/lib/api.ts`:
   ```ts
   listProjects: () => apiFetch<Project[]>("/api/projects"),
   ```
2. Call it — cookies attach automatically:
   ```tsx
   useEffect(() => { api.listProjects().then(setProjects); }, []);
   ```
3. Drop the page under `app/(app)/` — `(app)/layout.tsx` already wraps everything in `<ProtectedRoute><MainLayout>`.

Mutations are handled by `api.ts`: it ensures `/sanctum/csrf-cookie` has been hit, reads `XSRF-TOKEN`, and sends `X-XSRF-TOKEN`.

---

## 8. Frontend layout & route groups

```
client/src/app/
├── layout.tsx              ← AuthProvider + globals.css
├── (public)/
│   ├── layout.tsx          ← MainLayout
│   └── page.tsx            → /
├── (auth)/
│   ├── layout.tsx          ← MainLayout + centered flex wrapper
│   ├── login/page.tsx              → /login
│   ├── signup/page.tsx             → /signup
│   ├── forgot-password/page.tsx    → /forgot-password
│   └── reset-password/page.tsx     → /reset-password
└── (app)/
    ├── layout.tsx          ← <ProtectedRoute><MainLayout>
    └── dashboard/page.tsx          → /dashboard
```

Parens don't affect URLs — they're grouping for layout inheritance.

| Group     | Layout wraps in                  | Auth required |
| --------- | -------------------------------- | ------------- |
| `(public)`| `MainLayout`                     | No            |
| `(auth)`  | `MainLayout` + centered flex     | No            |
| `(app)`   | `ProtectedRoute` → `MainLayout`  | **Yes**       |

**Header is adaptive** (reads `useAuth().user` + `usePathname()`):

| User state | Page             | Right side                   |
| ---------- | ---------------- | ---------------------------- |
| Logged out | `/`              | Sign in · Get started        |
| Logged out | `/login`         | Get started                  |
| Logged out | `/signup`        | Sign in                      |
| Logged out | other auth pages | Sign in · Get started        |
| Logged in  | (anywhere)       | Dashboard · email · Logout   |

Mobile (`< md`) collapses everything into a hamburger that opens a right-side `Sheet` drawer.

---

## 9. Threat model — current posture

| Concern                  | Mitigation                                                                                | Where                                            |
| ------------------------ | ----------------------------------------------------------------------------------------- | ------------------------------------------------ |
| XSS exfiltrating token   | Auth is HttpOnly session cookie + CSRF; JS can't read it                                 | `bootstrap/app.php`, `lib/api.ts`                |
| Token never expires      | `SANCTUM_EXPIRATION=10080` for Bearer; SPA follows `SESSION_LIFETIME`                    | `config/sanctum.php`, `.env`                     |
| Login brute-force        | `throttle:5,1` (5 req/min/IP) on all public auth routes                                  | `routes/api.php`                                 |
| Login enumeration        | Generic 422 + `Hash::make()` on missing-user path                                         | `AuthController::login`                          |
| Register enumeration     | `unique:users` removed; manual lookup; identical 200 + matched bcrypt cost on both paths | `AuthController::register`                       |
| Password-reset enumeration | Always 200 with the same message                                                          | `PasswordResetController::sendResetLink`         |
| Account-hijack via dup signup | Existing user gets a `DuplicateRegistrationNotification` mail                          | `AuthController::register` + `Notifications/`    |
| New-account confirmation | New user gets a `WelcomeNotification` (paper trail for legitimate signups)               | `AuthController::register` + `Notifications/`    |
| CSRF                     | `statefulApi()` requires `X-XSRF-TOKEN` on mutations from stateful origins                | `bootstrap/app.php`                              |

### Still soft

- **Dev mailer is `log`** — emails land in `storage/logs/laravel.log`. Production must set `MAIL_MAILER=smtp|mailgun|ses|postmark` and a real `MAIL_FROM_ADDRESS`.
- **Cookie domain in production** — frontend + API must share a parent domain (`SESSION_DOMAIN=.example.com`). If they don't, fall back to Bearer auth.
- **No email verification gate** — fresh accounts can sign in immediately. Add `MustVerifyEmail` to `User` and the `verified` middleware if needed.
- **No multi-factor auth** — passwords are the only credential. TOTP / WebAuthn would be a follow-up.
