# Laravel + Vue

Monorepo with a Vue 3 SPA frontend (`client/`) and a Laravel backend (`backend/`). Deploys to a single AWS EC2 instance via GitHub Actions over SSH.

## Stack

- **Frontend**: Vue 3 (Composition API) + Vite + TypeScript (strict) + Pinia + Vue Router 4 + Tailwind + shadcn-vue + Axios + Vitest
- **Backend**: Laravel 13 + Sanctum (SPA cookie auth) + MySQL 8
- **CI/CD**: GitHub Actions → AWS EC2 (SSH + rsync) + RDS MySQL

## Folder structure

```
laravel_vue/
├── .github/workflows/                  # CI + deploy pipelines
├── backend/                            # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Auth/
│   │   │   ├── AuthController.php
│   │   │   └── PasswordResetController.php
│   │   ├── Models/User.php
│   │   ├── Notifications/
│   │   │   ├── WelcomeNotification.php
│   │   │   └── DuplicateRegistrationNotification.php
│   │   └── Providers/AppServiceProvider.php
│   ├── bootstrap/app.php               # statefulApi() enables Sanctum SPA mode
│   ├── config/{cors,sanctum,...}.php
│   ├── database/migrations/            # users, sessions, personal_access_tokens, cache, jobs
│   ├── routes/api.php                  # throttle:5,1 on auth; auth:sanctum on protected
│   ├── tests/Feature/Auth/AuthControllerTest.php
│   ├── .env.example
│   ├── composer.json
│   ├── phpunit.xml
│   └── pint.json
├── client/                             # Vue 3 SPA
│   ├── src/
│   │   ├── main.ts                     # createApp → Pinia → fetchUser → router → mount
│   │   ├── App.vue                     # layout switcher + <router-view>
│   │   ├── assets/index.css            # tailwind + shadcn CSS variables
│   │   ├── lib/
│   │   │   ├── api.ts                  # Axios + ensureCsrfCookie + 401 interceptor
│   │   │   └── utils.ts                # cn() helper
│   │   ├── stores/auth.ts              # Pinia setup-store (login/register/logout/etc.)
│   │   ├── composables/useApi.ts       # generic { data, error, loading, execute }
│   │   ├── router/index.ts             # routes + requiresAuth/guestOnly guards
│   │   ├── layouts/
│   │   │   ├── AuthLayout.vue          # centered card for guest pages
│   │   │   └── AppLayout.vue           # header + main shell for authed pages
│   │   ├── pages/
│   │   │   ├── LoginPage.vue           → /login
│   │   │   ├── SignupPage.vue          → /signup
│   │   │   ├── ForgotPasswordPage.vue  → /forgot-password
│   │   │   ├── ResetPasswordPage.vue   → /reset-password
│   │   │   └── DashboardPage.vue       → /dashboard (requiresAuth)
│   │   ├── components/
│   │   │   ├── AppHeader.vue
│   │   │   └── ui/                     # shadcn-vue: button, input, label, card
│   │   ├── types/user.ts
│   │   └── __tests__/                  # Vitest: auth store + LoginPage
│   ├── index.html
│   ├── package.json
│   ├── vite.config.ts                  # alias, port 3000, dev proxy to backend
│   ├── vitest.config.ts
│   ├── tailwind.config.js
│   └── tsconfig.{json,app.json,node.json}
├── deploy/                             # provisioning + canonical nginx config
└── docs/                               # CICD_FLOW.md, AUTH.md
```

## Initial setup

### 1. Clone

```bash
git clone git@github.com:<you>/laravel_next.git laravel_vue
cd laravel_vue
```

### 2. Backend — Laravel install

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Edit .env: set DB credentials and CORS/Sanctum origins
# DB_DATABASE=laravel_vue
# CORS_ALLOWED_ORIGINS=http://localhost:3000
# SANCTUM_STATEFUL_DOMAINS=localhost:3000

mysql -u root -e "CREATE DATABASE laravel_vue;"
php artisan migrate
php artisan serve   # → http://localhost:8000
```

### 3. Frontend — Vue install

```bash
cd client
npm install
cp .env.example .env.development
# Leave VITE_API_URL empty to use the Vite proxy (forwards /api + /sanctum to :8000)
npm run dev   # → http://localhost:3000
```

The Vite dev server proxies `/api` and `/sanctum` to the Laravel backend so the browser sees same-origin requests — no CORS or cross-site cookie shenanigans during local dev.

## Local commands

### Backend

```bash
cd backend
composer install
php artisan migrate            # run migrations
php artisan serve              # dev server
vendor/bin/phpunit             # run tests
vendor/bin/pint                # format code
vendor/bin/pint --test         # check style without changing files
```

### Frontend

```bash
cd client
npm install
npm run dev                    # Vite dev server (port 3000)
npm run lint                   # ESLint
npm run type-check             # vue-tsc
npm run test                   # Vitest (one-shot)
npm run test:watch             # Vitest watch mode
npm run build                  # production build → dist/
npm run preview                # serve the production build
```

## API endpoints

All public auth routes are rate-limited with `throttle:5,1` (5 req/min/IP). Protected routes use `auth:sanctum` (session cookie via SPA mode). See `docs/AUTH.md` for the full flow.

| Method | Path                          | Auth | Description                          |
| ------ | ----------------------------- | ---- | ------------------------------------ |
| POST   | `/api/register`               | No   | Create account (no email enumeration)|
| POST   | `/api/login`                  | No   | Establish session cookie             |
| POST   | `/api/password-reset`         | No   | Email a reset link (always 200)      |
| POST   | `/api/password-reset/confirm` | No   | Set new password with token          |
| POST   | `/api/logout`                 | Yes  | End session                          |
| GET    | `/api/user`                   | Yes  | Current authenticated user           |

## Pages

| Route              | Auth | Purpose                                  |
| ------------------ | ---- | ---------------------------------------- |
| `/`                | —    | Redirects to `/dashboard` or `/login`    |
| `/login`           | No   | Sign in                                  |
| `/signup`          | No   | Create account                           |
| `/forgot-password` | No   | Request password reset link              |
| `/reset-password`  | No   | Set new password (reached from email)    |
| `/dashboard`       | Yes  | Welcome screen + account card            |

## Deploying to AWS

Single EC2 `t2.micro` running both apps behind nginx, plus an RDS `db.t3.micro` MySQL instance. Workflows in `.github/workflows/` deploy on push to `main` via SSH + rsync.

### Architecture

```
GitHub push (main)
      │
      ▼
GitHub Actions runner
  • runs tests / lint / type-check / vitest
  • vite build → static dist/
      │  (rsync over SSH)
      ▼
EC2 t2.micro (Ubuntu)
  ├─ nginx :80               → fronts both
  ├─ PHP-FPM 8.5 (Laravel)   ← /api/*, /sanctum/*  (via fastcgi)
  └─ /var/www/laravel_vue/client/  ← static SPA served directly by nginx
                  │
                  ▼
       RDS MySQL 8 (db.t3.micro, private)
```

### Files in this repo that drive the deploy

| Path                                  | Purpose                                                      |
| ------------------------------------- | ------------------------------------------------------------ |
| `deploy/bootstrap.sh`                 | One-shot server provisioner (run once on a fresh EC2)        |
| `deploy/nginx.conf`                   | nginx vhost — `/api` + `/sanctum` → PHP-FPM, everything else → static SPA |
| `deploy/.env.production.example`      | Template for `backend/.env` on the server                    |
| `.github/workflows/backend.yml`       | Test + rsync + migrate + reload PHP-FPM                      |
| `.github/workflows/client.yml`        | Lint + type-check + vitest + vite build + rsync dist/        |

### One-time AWS setup (Console UI)

In `us-east-1`:

1. **EC2 key pair** — ED25519. Save private key to `~/.ssh/laravel-next-ec2.pem`, `chmod 400`.
2. **Security group `sg-laravel-ec2`** — inbound 22, 80, 443 from `0.0.0.0/0`.
3. **Security group `sg-laravel-rds`** — inbound 3306 from `sg-laravel-ec2`.
4. **EC2 instance** — Ubuntu LTS, t2.micro, 8 GB gp3, attach `sg-laravel-ec2` and the key pair. Name tag: `laravel-vue-ec2`.
5. **Elastic IP** — allocate, **associate** to the instance (free only while attached).
6. **RDS instance** — MySQL 8.0, free-tier template, `db.t3.micro`, 20 GB gp2, `sg-laravel-rds`, public access **No**, identifier `laravel-vue-rds`, initial DB `laravel_vue`.
7. **Budget alert** — Billing → Budgets → $1/mo budget at 80%.

### One-time server bootstrap

SSH in and run the bootstrap script:

```bash
ssh -i ~/.ssh/laravel-next-ec2.pem ubuntu@<elastic-ip>

# Pull bootstrap from the active branch (development until the first deploy on main)
curl -fsSL https://raw.githubusercontent.com/adevenuto/laravel_next/development/deploy/bootstrap.sh \
  | REPO_BRANCH=development bash
```

This installs nginx, PHP 8.5-FPM, Node 20, Composer, ufw; clones the repo to `/var/www/laravel_vue`; copies the canonical `deploy/nginx.conf` into `sites-available/laravel_vue` and enables it. (The GitHub repo is still named `laravel_next` even though the app was rebuilt as a Vue SPA — only the app-level naming uses `laravel_vue`.)

### One-time backend + first-build

```bash
# Configure backend env
sudo cp /var/www/laravel_vue/deploy/.env.production.example /var/www/laravel_vue/backend/.env
sudo nano /var/www/laravel_vue/backend/.env   # fill DB_HOST, DB_PASSWORD, APP_URL, CORS_ALLOWED_ORIGINS, SANCTUM_STATEFUL_DOMAINS

# Backend
cd /var/www/laravel_vue/backend
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --force
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Client (one-time build; CI rsyncs dist/ after that)
cd /var/www/laravel_vue/client
sudo -u ubuntu npm ci
sudo -u ubuntu VITE_API_URL=http://<elastic-ip> npm run build
# nginx already points at /var/www/laravel_vue/client; the build output lands there via dist/ rsync.
```

Smoke test from your laptop:

```bash
curl -sS http://<elastic-ip>/                                                            # SPA shell (index.html)
curl -sS -H 'Accept: application/json' -o /dev/null -w '%{http_code}\n' http://<elastic-ip>/api/user   # 401
```

### GitHub repository secrets

Repo → Settings → Secrets and variables → Actions:

| Secret          | Value                                                       |
| --------------- | ----------------------------------------------------------- |
| `EC2_HOST`      | Elastic IP                                                  |
| `EC2_USER`      | `ubuntu`                                                    |
| `EC2_SSH_KEY`   | Full contents of `~/.ssh/laravel-next-ec2.pem`              |
| `VITE_API_URL`  | `http://<elastic-ip>` (baked into the SPA bundle at build)  |

### Trigger a deploy

```bash
git push origin main
```

Both workflows are path-filtered, so only the relevant one fires.

## Pre-deployment checklist

- [ ] EC2 t2.micro launched in `us-east-1` with Elastic IP attached
- [ ] RDS db.t3.micro MySQL provisioned, `sg-laravel-rds` allows 3306 from `sg-laravel-ec2`, public access disabled
- [ ] `~/.ssh/laravel-next-ec2.pem` saved locally with `chmod 400`
- [ ] $1 AWS billing budget configured with email alert
- [ ] `bootstrap.sh` run on EC2; nginx + PHP-FPM systemd units active
- [ ] `backend/.env` filled in with RDS endpoint + password; `php artisan migrate --force` succeeded
- [ ] Client `dist/` built and served from `/var/www/laravel_vue/client/`
- [ ] Live URL smoke-tested: `/` returns SPA `index.html`, `/api/user` returns 401, register → login → dashboard works
- [ ] Four GitHub secrets set in repo
- [ ] `development` merged to `main`; both workflows green; subsequent push deploys without manual intervention
