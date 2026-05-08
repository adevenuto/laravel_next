# Authentication & Token Flow

This project uses **Laravel Sanctum in SPA mode**: the Next.js client authenticates via **HttpOnly session cookies** + **CSRF tokens** rather than Bearer tokens. Bearer tokens still work for non-SPA clients (e.g. tests, mobile, CLI), since `auth:sanctum` accepts both.

This doc covers:

1. The four auth lifecycle calls (register, login, logout, password reset)
2. How protected resource calls work (with CSRF)
3. Where the session cookie lives on the client
4. How Sanctum decides between cookie and Bearer auth
5. How to add a new protected resource endpoint
6. What's been hardened against (and what hasn't)

---

## High-level architecture

```
┌──────────────────────────────────┐         ┌────────────────────────────────────────┐
│  Next.js client (port 3000)      │         │  Laravel API (port 8000)               │
│                                  │         │                                        │
│  AuthContext ─► api.getUser()    │         │  routes/api.php                        │
│                  on mount        │         │   ├─ throttle:5,1                      │
│                                  │         │   │    register / login / pwd-reset    │
│  api.ts ──► fetch with:          │         │   └─ auth:sanctum:                     │
│   credentials: 'include'         │ cookies │        logout / user / + future        │
│   X-XSRF-TOKEN header (mutations)├─────────┼─►   EnsureFrontendRequestsAreStateful  │
│   /sanctum/csrf-cookie once      │         │        StartSession + ValidateCsrf     │
│                                  │         │   HasApiTokens for Bearer fallback     │
└──────────────────────────────────┘         └────────────────────────────────────────┘
```

Key files:

| Layer        | File                                                                  | Role                                              |
| ------------ | --------------------------------------------------------------------- | ------------------------------------------------- |
| Client       | `client/src/lib/api.ts`                                               | Cookie-aware fetch wrapper; CSRF helper           |
| Client       | `client/src/context/AuthContext.tsx`                                  | Hydrates `user` from `/api/user` on mount         |
| Client       | `client/src/components/ProtectedRoute.tsx`                            | Redirects to `/login` if no auth in context       |
| Backend      | `backend/bootstrap/app.php`                                           | `statefulApi()` enables SPA mode for stateful origins |
| Backend      | `backend/config/cors.php`                                             | `supports_credentials: true`, env-driven origins  |
| Backend      | `backend/config/sanctum.php`                                          | `expiration` (1 week) + stateful domains          |
| Backend      | `backend/routes/api.php`                                              | `throttle:5,1` on auth + `auth:sanctum` group     |
| Backend      | `backend/app/Http/Controllers/Auth/AuthController.php`                | Login, register, logout (session + token)         |
| Backend      | `backend/app/Http/Controllers/Auth/PasswordResetController.php`       | Always returns 200 (no enumeration)               |
| Backend      | `backend/app/Providers/AppServiceProvider.php`                        | Custom `ResetPassword` URL → frontend             |
| Backend      | `backend/app/Models/User.php`                                         | Uses `HasApiTokens` trait                         |

---

## Cookies, tokens, and CSRF — what's actually on the wire

| Cookie / header        | Set by              | Read by              | Notes                                         |
| ---------------------- | ------------------- | -------------------- | --------------------------------------------- |
| `laravel_session`      | Laravel             | Laravel              | **HttpOnly** — invisible to JS. Holds session id. |
| `XSRF-TOKEN`           | Laravel (via cookie) | JS (`document.cookie`) | **Not** HttpOnly. JS reads it, echoes it back as a header. |
| `X-XSRF-TOKEN` (header)| `api.ts` on mutations | Laravel               | CSRF check passes only if header matches the cookie. |
| `Authorization: Bearer …` | (only Bearer clients) | Laravel             | Optional; Sanctum falls back to this when no session cookie. |

The web client only ever uses the first three. Bearer tokens exist for tests and any non-SPA caller.

---

## 1. Register flow

```
Browser                   Next.js (AuthContext)              Laravel
   │                            │                                    │
   │  fill form, submit         │                                    │
   ├───────────────────────────►│                                    │
   │                            │  GET /sanctum/csrf-cookie          │
   │                            │  (first mutation only; cached)     │
   │                            ├───────────────────────────────────►│
   │                            │  Set-Cookie: XSRF-TOKEN=...        │
   │                            │              laravel_session=...   │
   │                            │◄───────────────────────────────────┤
   │                            │  POST /api/register                │
   │                            │  X-XSRF-TOKEN: <from cookie>       │
   │                            ├───────────────────────────────────►│
   │                            │                                    │  validate (no unique:users)
   │                            │                                    │  if email exists:
   │                            │                                    │     skip create; spend bcrypt round
   │                            │                                    │  else:
   │                            │                                    │     User::create(... Hash::make ...)
   │                            │  200 {message: "Your account is    │
   │                            │       ready. Please sign in."}     │
   │                            │◄───────────────────────────────────┤
   │  router.push('/login?registered=1')                              │
   │◄───────────────────────────┤
```

The response is identical whether or not the email already exists, and there is **no auto-login** — the user is redirected to `/login`, which shows a green success banner when `?registered=1` is present.

---

## 2. Login flow

```
Browser                   Next.js                             Laravel
   │                            │                                    │
   │  email + password          │                                    │
   ├───────────────────────────►│                                    │
   │                            │  POST /api/login                   │
   │                            │  + CSRF + cookies                  │
   │                            ├───────────────────────────────────►│
   │                            │                                    │  User::where(email)->first()
   │                            │                                    │  if user:  Hash::check(...)
   │                            │                                    │  else:     Hash::make(...) ← timing
   │                            │                                    │  on fail → 422 generic message
   │                            │                                    │  on success → Auth::login + session regen
   │                            │  200 {user}    or  422 {errors}    │
   │                            │◄───────────────────────────────────┤
   │  /dashboard                │                                    │
   │◄───────────────────────────┤
```

The login endpoint runs a `Hash::make` even when the email is unknown, so attackers can't time-fingerprint "no such user" vs "wrong password".

The response is the same 422 shape regardless of whether the email exists or the password is wrong — both surface as a single `email` validation error reading "These credentials do not match our records."

`/api/login`, `/api/register`, and `/api/password-reset` are rate-limited to **5 requests per minute per IP** (`throttle:5,1`).

---

## 3. Authenticated request flow (the request cycle for any protected resource)

This is the pattern every future protected route follows.

```
Browser                Next.js (api.ts)                     Laravel
   │                         │                                    │
   │  user clicks "load X"   │                                    │
   ├────────────────────────►│                                    │
   │                         │  fetch(`${API}/api/things/42`, {    │
   │                         │    headers: { Accept: ..., }       │
   │                         │    credentials: 'include',          │
   │                         │    /* X-XSRF-TOKEN on mutations */  │
   │                         │  })                                 │
   │                         ├────────────────────────────────────►│
   │                         │                                    │  ① CORS preflight (OPTIONS)
   │                         │                                    │     → HandleCors checks Origin
   │                         │                                    │     → 204 + ACAO + ACAC: true
   │                         │                                    │  ② Actual request
   │                         │                                    │  ③ HandleCors echoes Origin
   │                         │                                    │  ④ EnsureFrontendRequestsAreStateful
   │                         │                                    │     - Origin in stateful list?
   │                         │                                    │     - if yes: prepend session +
   │                         │                                    │       cookie + CSRF middleware
   │                         │                                    │  ⑤ StartSession → loads session
   │                         │                                    │  ⑥ ValidateCsrfToken (mutations)
   │                         │                                    │  ⑦ auth:sanctum:
   │                         │                                    │     - has session user? use it
   │                         │                                    │     - else parse Bearer token
   │                         │                                    │     - else 401
   │                         │                                    │  ⑧ Controller, JSON response
   │                         │  200 {data}                        │
   │                         │◄────────────────────────────────────┤
```

Notes:

- **CSRF**: only enforced on mutations (POST/PUT/PATCH/DELETE). The client reads `XSRF-TOKEN` from `document.cookie` and forwards it as `X-XSRF-TOKEN`. Laravel decodes the header and compares to the session-bound CSRF.
- **`credentials: 'include'`**: required so the browser sends/receives the `laravel_session` cookie cross-origin. Pairs with `supports_credentials: true` in `config/cors.php`.
- **CORS** must list specific origins (no `*`) when `supports_credentials` is on. `config/cors.php` reads `CORS_ALLOWED_ORIGINS` from `.env`.
- **`auth:sanctum`** is dual-mode: cookie first, Bearer fallback. No code path needs to know which one a given client is using.

---

## 4. Logout flow

```
Browser                Next.js                             Laravel
   │                         │                                    │
   │  click Logout           │                                    │
   ├────────────────────────►│                                    │
   │                         │  POST /api/logout                   │
   │                         │  + CSRF + cookies                  │
   │                         ├────────────────────────────────────►│
   │                         │                                    │  Capture currentAccessToken (if any)
   │                         │                                    │  Auth::guard('web')->logout()
   │                         │                                    │  session()->invalidate()
   │                         │                                    │  session()->regenerateToken()
   │                         │                                    │  $token?->delete()  ← Bearer revoke
   │                         │  200 {message}                     │
   │                         │◄────────────────────────────────────┤
   │                         │  setUser(null)
   │  /login                 │                                    │
   │◄────────────────────────┤
```

The logout handler is dual-mode: it ends the SPA session AND revokes the personal access token (if the request used one). Other devices/sessions stay logged in.

---

## 5. Password reset (full flow)

The reset is a two-step flow:

**Step 1 — request a link**: `POST /api/password-reset` is public. It calls `Password::sendResetLink()` and **always** returns `200` with the same generic message, regardless of whether the email exists. This prevents user enumeration.

The email links to the frontend (configured in `AppServiceProvider`):

```
{FRONTEND_URL}/reset-password?token=<token>&email=<email>
```

In dev with `MAIL_MAILER=log`, the link gets written to `backend/storage/logs/laravel.log` — open the file, paste the URL into the browser.

**Step 2 — confirm with new password**: `POST /api/password-reset/confirm` accepts `{email, token, password, password_confirmation}` and calls `Password::reset()`, which validates the token (single-use, time-limited per `config/auth.php → passwords.users.expire`), updates the password hash, and rotates `remember_token`. Returns `200 {message}` on success or `422` if the token is invalid/expired.

The frontend page at `client/src/app/reset-password/page.tsx`:
- Reads `token` and `email` from the query string
- Renders a "new password" form
- Posts to `/api/password-reset/confirm`
- Redirects to `/login?reset=1` on success — the login page reads that flag and shows a green success banner.

---

## 6. Page reload / session restoration

There is no localStorage. On mount, `AuthProvider` calls `GET /api/user`. The browser automatically attaches the `laravel_session` cookie (because every fetch uses `credentials: 'include'`). If the session is valid, the server returns the user; otherwise the call 401s and the context stays in the logged-out state.

```
Page loads
  ↓
AuthProvider mounts
  ↓
api.getUser()
  - browser sends laravel_session cookie automatically
  - server validates session, returns user
  ↓
- success → setUser(user); setIsLoading(false)
- 401     → setUser(null); setIsLoading(false)
  ↓
ProtectedRoute checks (user, isLoading):
  - isLoading → show "Loading..."
  - !user     → router.replace('/login')
  - else      → render children
```

Pros over localStorage: the token is **HttpOnly** — JS-injected scripts can't exfiltrate it. Cons: requires CSRF plumbing, and the frontend + API must share a parent domain in production for cookies to ride along.

---

## 7. Adding a new protected resource endpoint

Use this as a checklist when wiring up the next feature (e.g. `GET /api/projects`).

### Backend

1. **Add the route** under the Sanctum-guarded group in `backend/routes/api.php`:
   ```php
   Route::middleware('auth:sanctum')->group(function () {
       Route::post('/logout', [AuthController::class, 'logout']);
       Route::get('/user',    [AuthController::class, 'user']);
       Route::get('/projects', [ProjectController::class, 'index']);  // new
   });
   ```
2. **Read the user** in the controller via `$request->user()` — the authenticated `App\Models\User`.
3. **Authorize** if needed (Policy + `$this->authorize(...)`, or manual ownership checks).
4. **Test with Sanctum::actingAs or `actingAs($user)`**:
   ```php
   $this->actingAs($user)->getJson('/api/projects')->assertOk();
   ```
   Or with a Bearer token, which still works:
   ```php
   $token = $user->createToken('test')->plainTextToken;
   $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/projects')->assertOk();
   ```

### Client

1. **Add a method** to `client/src/lib/api.ts`:
   ```ts
   listProjects: () => apiFetch<Project[]>("/api/projects"),
   ```
   No token argument needed — cookies ride along automatically.
2. **Call it** from a component:
   ```tsx
   useEffect(() => {
     api.listProjects().then(setProjects).catch(setError);
   }, []);
   ```
3. **Wrap the page** in `<ProtectedRoute>` (see `dashboard/page.tsx`).

For a mutation (POST/PUT/DELETE), `api.ts` automatically:
- ensures `/sanctum/csrf-cookie` has been called once
- reads the `XSRF-TOKEN` cookie
- forwards it as `X-XSRF-TOKEN`

---

## 8. Threat model — current posture

| Concern                  | Mitigation in place                                                                                                       | File                                            |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------- |
| **XSS exfiltrating token** | Auth is HttpOnly session cookie + CSRF; JS can't read the session token. (Bearer tokens still exist for non-SPA clients.) | `bootstrap/app.php`, `config/cors.php`, `lib/api.ts` |
| **Token never expires**  | `SANCTUM_EXPIRATION=10080` (1 week) — applies to Bearer tokens. SPA sessions follow `SESSION_LIFETIME` (120 min default). | `config/sanctum.php`, `.env.example`            |
| **Login brute-force**    | `throttle:5,1` on `/api/login`, `/api/register`, `/api/password-reset` (5 req/min/IP).                                    | `routes/api.php`                                |
| **User enumeration**     | Login: generic 422 + `Hash::make()` on missing-user path for uniform timing. Password reset: always 200, same message.    | `AuthController::login`, `PasswordResetController` |
| **Reset email leakage**  | Email link points at the frontend (configurable via `FRONTEND_URL`); reset endpoint never confirms whether the address is registered. | `AppServiceProvider::boot()`                |
| **CSRF**                 | Enabled by `statefulApi()` for any mutation from a stateful origin. Required `X-XSRF-TOKEN` header.                       | `bootstrap/app.php`, Laravel default            |

### Recently hardened

- **Register email enumeration**: the `unique:users` validator was removed. Register looks up by email manually; if the email exists, no duplicate is created and the controller still spends one bcrypt round (timing parity with the create path). Same `200 {message}` response either way. Register no longer auto-logs in — caller is redirected to `/login?registered=1`.
- **Password reset confirmation**: `POST /api/password-reset/confirm` is implemented (rate-limited 5/min). Frontend page at `/reset-password` consumes the email link's `?token=&email=` query.

### Still soft (deliberate trade-offs for a learning project)

- **No "someone tried to register with your email" notice**: the existing user is not currently emailed when a duplicate signup is attempted. A `TODO` comment marks the spot in `AuthController::register`. Adding it is a single notification class plus a `Notification::send(...)` line.
- **No welcome email on real signup**: a `TODO` marks where it would go. Skipped because `MAIL_MAILER=log` in dev makes it noise.
- **MAIL_MAILER=log in dev**: reset links land in `backend/storage/logs/laravel.log`. Switch to `smtp`/`mailgun`/`ses` in production via `.env`.
- **Cookie domain in production**: requires the frontend and API to share a parent domain (e.g. `app.example.com` and `api.example.com` both set `SESSION_DOMAIN=.example.com`). Otherwise, fall back to Bearer tokens.
