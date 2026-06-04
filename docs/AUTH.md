# Authentication & Token Flow

This project uses **Laravel Sanctum in SPA mode**: the Vue 3 client authenticates via **HttpOnly session cookies + CSRF**, not Bearer tokens. Bearer tokens still work for non-SPA clients (tests, mobile, CLI) — `auth:sanctum` accepts both.

---

## High-level architecture

```
┌──────────────────────────────────┐         ┌────────────────────────────────────────┐
│  Vue 3 SPA (port 3000)           │         │  Laravel API (port 8000)               │
│                                  │         │                                        │
│  main.ts ─► auth.fetchUser()     │         │  routes/api.php                        │
│   before mount                   │         │   ├─ throttle:5,1                      │
│  lib/api.ts ─► axios w/          │ cookies │   │    register / login                 │
│   withCredentials +              ├─────────┼─►   │    password-reset (+ confirm)     │
│   withXSRFToken                  │         │   └─ auth:sanctum                      │
│  /sanctum/csrf-cookie once       │         │   EnsureFrontendRequestsAreStateful    │
│                                  │         │      → StartSession + ValidateCsrf     │
│  Router guards:                  │         │   HasApiTokens for Bearer fallback     │
│   requiresAuth / guestOnly       │         │                                        │
└──────────────────────────────────┘         └────────────────────────────────────────┘
```

### Key files

| Layer   | File                                                                  | Role                                          |
| ------- | --------------------------------------------------------------------- | --------------------------------------------- |
| Client  | `client/src/lib/api.ts`                                               | Axios instance; `ensureCsrfCookie()`; 401 interceptor |
| Client  | `client/src/stores/auth.ts`                                           | Pinia store: `login`, `register`, `logout`, `fetchUser`, `forgotPassword`, `resetPassword` |
| Client  | `client/src/composables/useApi.ts`                                    | Generic `{ data, error, loading, execute }` wrapper |
| Client  | `client/src/router/index.ts`                                          | Routes + `beforeEach` guard (`requiresAuth` / `guestOnly`) |
| Client  | `client/src/main.ts`                                                  | Hydrates `auth.fetchUser()` before installing router and mounting |
| Client  | `client/src/layouts/{Auth,App}Layout.vue`                             | Guest vs authed chrome (chosen by `route.meta.layout`) |
| Client  | `client/src/components/AppHeader.vue`                                 | Header with user name + logout                |
| Backend | `backend/bootstrap/app.php`                                           | `statefulApi()` enables SPA mode              |
| Backend | `backend/config/cors.php`                                             | `supports_credentials: true`; env origins     |
| Backend | `backend/config/sanctum.php`                                          | Expiration + stateful domains                 |
| Backend | `backend/routes/api.php`                                              | `throttle:5,1` on auth + `auth:sanctum` group |
| Backend | `backend/app/Http/Controllers/Auth/*.php`                             | Auth + password-reset controllers             |
| Backend | `backend/app/Notifications/WelcomeNotification.php`                   | Welcome email on real signup                  |
| Backend | `backend/app/Providers/AppServiceProvider.php`                        | Custom `ResetPassword` URL → frontend         |

### Wire-level cookies & headers

| Thing                   | Set by               | Read by                 | Notes                                                |
| ----------------------- | -------------------- | ----------------------- | ---------------------------------------------------- |
| `laravel_session`       | Laravel              | Laravel                 | **HttpOnly** — invisible to JS                       |
| `XSRF-TOKEN` cookie     | Laravel              | Axios (`withXSRFToken`) | Not HttpOnly — Axios echoes it as a header           |
| `X-XSRF-TOKEN` header   | Axios (mutations)    | Laravel                 | Must match the cookie                                |
| `Authorization: Bearer` | Bearer clients only  | Laravel                 | Sanctum fallback when no session                     |

---

## 1. Register flow

```
Browser              Vue (auth store)                    Laravel
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
   │                       │                                    │ validate (incl. unique:users,email)
   │                       │                                    │ on success:
   │                       │                                    │   User::create() + WelcomeNotification
   │                       │                                    │   Auth::login() + session regenerate
   │                       │ 200 {user}   ◄── on success         │
   │                       │ 422 {errors.email} ◄── on duplicate │
   │                       │◄───────────────────────────────────┤
   │ user.value = data; router.push('/dashboard')  ◄── on success│
   │◄──────────────────────┤
```

- **Auto-login**: a successful register session-regenerates and returns the user object; the store sets `user.value` and the page redirects straight to `/dashboard` (no `/login` bounce).
- **Duplicate email** is revealed via 422 with `errors.email = "The email has already been taken."` — `useApi` surfaces this in the form's error slot.
- **Side effect**: new user gets a `WelcomeNotification` mail.
- **Enumeration trade-off**: anti-enumeration mitigation was dropped in favor of standard UX. Rate limiting (`throttle:5,1`, 5/min/IP) is the remaining brute-force protection.
- Dev: `MAIL_MAILER=log` writes the welcome mail to `backend/storage/logs/laravel.log`.

---

## 2. Login flow

```
Browser           Vue                              Laravel
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
Browser         Vue (lib/api.ts)                  Laravel
   │                  │                                    │
   ├─────────────────►│                                    │
   │                  │ api.get(`/api/things/42`)          │
   │                  │   axios.create({                   │
   │                  │     withCredentials: true,         │
   │                  │     withXSRFToken: true,           │
   │                  │   })                               │
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

- CSRF is enforced on **mutations only** (POST/PUT/PATCH/DELETE). Axios `withXSRFToken: true` reads the cookie and sets `X-XSRF-TOKEN` automatically — no manual handling needed.
- `withCredentials: true` is required cross-origin and pairs with `supports_credentials: true` on the server.
- `auth:sanctum` is dual-mode: cookie first, Bearer fallback — controllers don't need to know which.
- In dev, the Vite proxy forwards `/api` + `/sanctum` → `http://localhost:8000`, so the browser sees same-origin requests and the cookie handling is trivial.

---

## 4. Logout flow

```
Browser        Vue                               Laravel
   │              │                                    │
   ├─────────────►│ POST /api/logout + cookies + CSRF  │
   │              ├───────────────────────────────────►│
   │              │                                    │ token = $request->user()?->currentAccessToken()
   │              │                                    │ Auth::guard('web')->logout()
   │              │                                    │ session()->invalidate() + regenerateToken()
   │              │                                    │ $token?->delete()  ← Bearer revoke
   │              │ 200 {message}                      │
   │              │◄───────────────────────────────────┤
   │              │ auth.clear(); resetCsrf()
   │ /login       │
   │◄─────────────┤
```

Ends the SPA session AND revokes the personal access token (if the request used one). Other devices stay logged in. The store calls `resetCsrf()` so the next mutation re-fetches a fresh CSRF cookie.

---

## 5. Password reset

Two steps.

**Request a link** — `POST /api/password-reset`. Always returns `200` with a generic message (no enumeration). The email links to:

```
{FRONTEND_URL}/reset-password?token=<token>&email=<email>
```

In dev the link lands in `backend/storage/logs/laravel.log`.

**Confirm with new password** — `POST /api/password-reset/confirm` accepts `{email, token, password, password_confirmation}`, calls `Password::reset()` (validates single-use/time-limited token, updates hash, rotates `remember_token`). 200 on success, 422 on invalid/expired token.

The frontend page (`client/src/pages/ResetPasswordPage.vue`) reads `token`+`email` from the route query via `useRoute()`, posts the new password, and redirects to `/login?reset=1` on success.

---

## 6. Page reload / session restoration

No localStorage. In `main.ts`, before installing the router and mounting:

```ts
const auth = useAuthStore()
await auth.fetchUser().catch(() => {})  // 401 = anonymous, fine
app.use(router)
await router.isReady()
app.mount('#app')
```

- 200 → `user.value = data`
- 401 → `user.value` stays null

This order matters: the router's `beforeEach` guard reads `auth.isAuthenticated`, so the store has to be hydrated before any navigation can resolve. Otherwise a refresh on `/dashboard` would briefly see "not authenticated" and bounce to `/login`.

Trade-off: HttpOnly session cookie means JS can't exfiltrate it, but the frontend and API must share a parent domain in production for cookies to ride along.

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

1. Add an action to the relevant Pinia store, or call `api` directly:
   ```ts
   import { api } from '@/lib/api'
   const { data } = await api.get<Project[]>('/api/projects')
   ```
2. Wrap with `useApi` in the page/component if you want reactive loading/error:
   ```ts
   const { data: projects, error, loading, execute } = useApi(() => api.get<Project[]>('/api/projects').then(r => r.data))
   onMounted(() => execute())
   ```
3. Add the route to `client/src/router/index.ts` with `meta: { requiresAuth: true, layout: 'app' }` — the global guard will gate it, and `AppLayout` provides the header + chrome.

Mutations are handled automatically by `lib/api.ts`: Axios reads the `XSRF-TOKEN` cookie and sends `X-XSRF-TOKEN` on every POST/PUT/PATCH/DELETE.

---

## 8. Frontend routing & layouts

Vue Router 4 with two layouts chosen via `route.meta.layout`:

```ts
const routes = [
  { path: '/',                redirect: () => useAuthStore().isAuthenticated ? { name: 'dashboard' } : { name: 'login' } },
  { path: '/login',           name: 'login',           component: LoginPage,           meta: { layout: 'auth', guestOnly: true } },
  { path: '/signup',          name: 'signup',          component: SignupPage,          meta: { layout: 'auth', guestOnly: true } },
  { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordPage,  meta: { layout: 'auth', guestOnly: true } },
  { path: '/reset-password',  name: 'reset-password',  component: ResetPasswordPage,   meta: { layout: 'auth', guestOnly: true } },
  { path: '/dashboard',       name: 'dashboard',       component: DashboardPage,       meta: { layout: 'app',  requiresAuth: true } },
  { path: '/:pathMatch(.*)*', redirect: { name: 'root' } },
]

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return { name: 'login' }
  if (to.meta.guestOnly && auth.isAuthenticated)     return { name: 'dashboard' }
})
```

| Meta             | Behavior                                                       |
| ---------------- | -------------------------------------------------------------- |
| `requiresAuth`   | Guest visitors → bounced to `/login`                           |
| `guestOnly`      | Authed visitors → bounced to `/dashboard`                      |
| `layout: 'auth'` | Rendered inside `AuthLayout` (centered card)                   |
| `layout: 'app'`  | Rendered inside `AppLayout` (header with user name + logout)   |

`App.vue` is a layout switcher: `<component :is="layoutComponent"><router-view /></component>`.

---

## 9. Threat model — current posture

| Concern                  | Mitigation                                                                                | Where                                            |
| ------------------------ | ----------------------------------------------------------------------------------------- | ------------------------------------------------ |
| XSS exfiltrating token   | Auth is HttpOnly session cookie + CSRF; JS can't read it                                 | `bootstrap/app.php`, `lib/api.ts`                |
| Token never expires      | `SANCTUM_EXPIRATION=10080` for Bearer; SPA follows `SESSION_LIFETIME`                    | `config/sanctum.php`, `.env`                     |
| Login brute-force        | `throttle:5,1` (5 req/min/IP) on all public auth routes                                  | `routes/api.php`                                 |
| Login enumeration        | Generic 422 + `Hash::make()` on missing-user path                                         | `AuthController::login`                          |
| Register enumeration     | Deliberately revealed via 422 on duplicate email; rate-limited at 5/min/IP via `throttle:5,1` | `AuthController::register` + `routes/api.php`    |
| Password-reset enumeration | Always 200 with the same message                                                          | `PasswordResetController::sendResetLink`         |
| New-account confirmation | New user gets a `WelcomeNotification` (paper trail for legitimate signups)               | `AuthController::register` + `Notifications/`    |
| CSRF                     | `statefulApi()` requires `X-XSRF-TOKEN` on mutations from stateful origins                | `bootstrap/app.php`                              |

### Still soft

- **Dev mailer is `log`** — emails land in `storage/logs/laravel.log`. Production must set `MAIL_MAILER=smtp|mailgun|ses|postmark` and a real `MAIL_FROM_ADDRESS`.
- **Cookie domain in production** — frontend + API must share a parent domain (`SESSION_DOMAIN=.example.com`). If they don't, fall back to Bearer auth.
- **No email verification gate** — fresh accounts can sign in immediately. Add `MustVerifyEmail` to `User` and the `verified` middleware if needed.
- **No multi-factor auth** — passwords are the only credential. TOTP / WebAuthn would be a follow-up.
- **Non-JSON 401 path** — unauth requests to `/api/*` without `Accept: application/json` currently 500 trying to redirect to a `login` route that doesn't exist. The Vue client always sends the Accept header, so this is invisible in normal use; fix is to register `shouldRenderJsonWhen` in `bootstrap/app.php` for any path matching `api/*`.
