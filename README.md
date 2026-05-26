# Next + Laravel

Monorepo with a Next.js frontend (`client/`) and a Laravel backend (`backend/`). Deploys to Azure via GitHub Actions.

## Stack

- **Frontend**: Next.js 15 (App Router) + TypeScript + Tailwind + shadcn/ui
- **Backend**: Laravel 11 + Sanctum (token auth) + MySQL
- **CI/CD**: GitHub Actions → Azure App Service (OIDC)

## Folder structure

```
next_laravel/
├── .github/workflows/                  # CI + deploy pipelines (in progress)
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
├── client/                             # Next.js app
│   ├── src/
│   │   ├── app/
│   │   │   ├── layout.tsx                       # AuthProvider
│   │   │   ├── globals.css
│   │   │   ├── (public)/
│   │   │   │   ├── layout.tsx                   # MainLayout
│   │   │   │   └── page.tsx                     → /
│   │   │   ├── (auth)/
│   │   │   │   ├── layout.tsx                   # MainLayout + centered flex
│   │   │   │   ├── login/page.tsx               → /login
│   │   │   │   ├── signup/page.tsx              → /signup
│   │   │   │   ├── forgot-password/page.tsx     → /forgot-password
│   │   │   │   └── reset-password/page.tsx      → /reset-password
│   │   │   └── (app)/
│   │   │       ├── layout.tsx                   # <ProtectedRoute><MainLayout>
│   │   │       └── dashboard/page.tsx           → /dashboard
│   │   ├── components/
│   │   │   ├── Header.tsx                       # adaptive nav + mobile Sheet
│   │   │   ├── Footer.tsx
│   │   │   ├── MainLayout.tsx                   # Header + main + Footer shell
│   │   │   ├── ProtectedRoute.tsx
│   │   │   └── ui/                              # shadcn: button, card, input, label, sheet
│   │   ├── context/AuthContext.tsx              # hydrates user from /api/user
│   │   ├── lib/api.ts                           # cookie-aware fetch + CSRF helper
│   │   └── lib/utils.ts
│   ├── .env.example
│   ├── .eslintrc.json
│   ├── next.config.ts
│   ├── package.json
│   ├── postcss.config.mjs
│   ├── tailwind.config.ts
│   └── tsconfig.json
└── docs/AUTH.md                        # Auth architecture deep-dive
```

## Initial setup

### 1. Initialize git repo

```bash
cd next_laravel
git init
git add .
git commit -m "Initial scaffold"
git branch -M main
git remote add origin git@github.com:<you>/next_laravel.git
git push -u origin main
```

### 2. Backend — Laravel install

The Laravel skeleton is fully committed (`bootstrap/`, `public/`, `artisan`, full `config/`). A fresh clone only needs vendor + env setup.

```bash
cd backend

# Install dependencies
composer install

# Copy env and generate key
cp .env.example .env
php artisan key:generate

# Edit .env: set DB credentials and CORS_ALLOWED_ORIGINS
# DB_DATABASE=next_laravel
# CORS_ALLOWED_ORIGINS=http://localhost:3000

# Create the database (MySQL)
mysql -u root -e "CREATE DATABASE next_laravel;"

# Run migrations
php artisan migrate

# Serve
php artisan serve   # → http://localhost:8000
```

### 3. Frontend — Next.js install

```bash
cd client
npm install
cp .env.example .env.local
# .env.local already points to NEXT_PUBLIC_API_URL=http://localhost:8000
npm run dev   # → http://localhost:3000
```

### 4. shadcn/ui (already wired up)

The components used (`button`, `card`, `input`, `label`, `sheet`) are committed under `client/src/components/ui/`. To add more later:

```bash
cd client
npx shadcn@latest add dialog
```

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
npm run dev                    # dev server
npm run lint                   # ESLint
npm run type-check             # TypeScript
npm run build                  # production build
npm start                      # serve production build
```

## API endpoints

All public auth routes are rate-limited with `throttle:5,1` (5 req/min/IP). Protected routes use `auth:sanctum` (session cookie or Bearer token). See `docs/AUTH.md` for the full flow.

| Method | Path                          | Auth | Description                          |
| ------ | ----------------------------- | ---- | ------------------------------------ |
| POST   | `/api/register`               | No   | Create account (no email enumeration)|
| POST   | `/api/login`                  | No   | Establish session / issue token      |
| POST   | `/api/password-reset`         | No   | Email a reset link (always 200)      |
| POST   | `/api/password-reset/confirm` | No   | Set new password with token          |
| POST   | `/api/logout`                 | Yes  | End session + revoke token           |
| GET    | `/api/user`                   | Yes  | Current authenticated user           |

## Pages

| Route              | Auth | Purpose                                  |
| ------------------ | ---- | ---------------------------------------- |
| `/`                | No   | Landing page                             |
| `/login`           | No   | Sign in                                  |
| `/signup`          | No   | Create account                           |
| `/forgot-password` | No   | Request password reset link              |
| `/reset-password`  | No   | Set new password (reached from email)    |
| `/dashboard`       | Yes  | Welcome, {user.name} screen              |

## Deploying to Azure

> **Outline only.** Workflow YAML and `az` CLI step-by-steps are a follow-up. Target stack: **Azure App Service (Linux)** for both apps + **Azure Database for MySQL Flexible Server**. GitHub Actions authenticates to Azure via **OIDC federated credentials** (no long-lived secrets).

### Azure resources to create

1. **Resource group** — one group to hold everything (e.g. `rg-next-laravel`).
2. **App Service Plan** — Linux, B1 or higher.
3. **App Service: backend** — runtime PHP 8.x; document root → `backend/public`.
4. **App Service: client** — runtime Node 20+; start command runs the built Next.js standalone output.
5. **Azure Database for MySQL Flexible Server** — same region as the App Services. Create a `next_laravel` database; configure the firewall.
6. **Azure AD app registration** — for GitHub Actions OIDC. Add federated credentials for the repo's `main` branch and grant the registration the Contributor role on the resource group.
7. **Custom domains + TLS** — bind `yourdomain.com` to the client App Service and `api.yourdomain.com` to the backend App Service; use App Service Managed Certificates for TLS.

### GitHub repository secrets

In repo → Settings → Secrets and variables → Actions:

| Secret                   | Source                                              |
| ------------------------ | --------------------------------------------------- |
| `AZURE_TENANT_ID`        | Azure AD tenant of the app registration             |
| `AZURE_CLIENT_ID`        | Client ID of the app registration                   |
| `AZURE_SUBSCRIPTION_ID`  | Subscription holding the resource group             |
| `AZURE_BACKEND_APP_NAME` | App Service name for Laravel                        |
| `AZURE_CLIENT_APP_NAME`  | App Service name for Next.js                        |
| `NEXT_PUBLIC_API_URL`    | `https://api.yourdomain.com`                        |

DB credentials, `APP_KEY`, and `CORS_ALLOWED_ORIGINS` live as **App Settings** on the backend App Service — not as GitHub secrets.

### One-time backend bootstrap (after first deploy)

Open the backend App Service → SSH (or `az webapp ssh -g <rg> -n <app>`):

```bash
cd /home/site/wwwroot
cp .env.example .env
php artisan key:generate
# DB_*, APP_URL, CORS_ALLOWED_ORIGINS come from App Settings (preferred)
php artisan migrate --force
```

### Trigger a deploy

```bash
git push origin main
```

Azure-targeted workflows will live in `.github/workflows/` (to be authored as a follow-up).

## Pre-deployment checklist

- [ ] Resource group + App Service Plan + two App Services + MySQL Flexible Server provisioned
- [ ] Azure AD app registration created; federated credentials added for this repo's `main` branch
- [ ] All six GitHub secrets set
- [ ] DB credentials, `APP_KEY`, `CORS_ALLOWED_ORIGINS` set as App Settings on the backend App Service
- [ ] First-run migrations executed (`php artisan migrate --force`)
- [ ] Custom domains bound + TLS issued on both App Services
- [ ] Backend App Service document root set to `backend/public`
- [ ] Workflows authored in `.github/workflows/` and verified via a test push to `main`
