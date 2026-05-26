# Next + Laravel

Monorepo with a Next.js frontend (`client/`) and a Laravel backend (`backend/`). Deploys to a single AWS EC2 instance (free-tier) via GitHub Actions over SSH.

## Stack

- **Frontend**: Next.js 15 (App Router) + TypeScript + Tailwind + shadcn/ui
- **Backend**: Laravel 11 + Sanctum (token auth) + MySQL
- **CI/CD**: GitHub Actions → AWS EC2 (SSH + rsync) + RDS MySQL

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

## Deploying to AWS

Free-tier setup, designed to cost **$0/mo for 12 months**: a single EC2 `t2.micro` running both apps behind nginx, plus an RDS `db.t3.micro` MySQL instance. Workflows in `.github/workflows/` deploy on push to `main` via SSH + rsync.

### Architecture

```
GitHub push (main)
      │
      ▼
GitHub Actions runner
  • runs tests / lint / type-check
  • builds Next.js standalone bundle
      │  (rsync over SSH)
      ▼
EC2 t2.micro (Ubuntu 22.04)
  ├─ nginx :80               → reverse proxy
  ├─ PHP-FPM 8.3 (Laravel)   ← /api/*, /sanctum/*
  └─ Node 20 + pm2 (Next.js) ← everything else (127.0.0.1:3000)
                  │
                  ▼
       RDS MySQL 8.0 (db.t3.micro, private)
```

### Files in this repo that drive the deploy

| Path                                  | Purpose                                                      |
| ------------------------------------- | ------------------------------------------------------------ |
| `deploy/bootstrap.sh`                 | One-shot server provisioner (run once on a fresh EC2)        |
| `deploy/nginx.conf`                   | nginx vhost — splits traffic between Laravel and Next.js     |
| `deploy/.env.production.example`      | Template for `backend/.env` on the server                    |
| `.github/workflows/backend.yml`       | Test + rsync + migrate + reload PHP-FPM                      |
| `.github/workflows/client.yml`        | Lint + build standalone + rsync + pm2 reload                 |

### One-time AWS setup (Console UI)

In `us-east-1`:

1. **EC2 key pair** — `laravel-next-ec2`, ED25519. Save private key to `~/.ssh/laravel-next-ec2.pem`, `chmod 400`.
2. **Security group `sg-laravel-ec2`** — inbound 22, 80, 443 from `0.0.0.0/0`.
3. **Security group `sg-laravel-rds`** — inbound 3306 from `sg-laravel-ec2`.
4. **EC2 instance** — Ubuntu 22.04 LTS, t2.micro, 8 GB gp3, attach `sg-laravel-ec2` and the key pair.
5. **Elastic IP** — allocate, **associate** to the instance (free only while attached).
6. **RDS instance** — MySQL 8.0, free-tier template, `db.t3.micro`, 20 GB gp2, `sg-laravel-rds`, public access **No**, initial DB `next_laravel`.
7. **Budget alert** — Billing → Budgets → $1/mo budget at 80%.

### One-time server bootstrap

SSH in and run the bootstrap script:

```bash
ssh -i ~/.ssh/laravel-next-ec2.pem ubuntu@<elastic-ip>

# Pull bootstrap from the active branch (development until the first deploy on main)
curl -fsSL https://raw.githubusercontent.com/adevenuto/laravel_next/development/deploy/bootstrap.sh \
  | REPO_BRANCH=development bash
```

This installs nginx, PHP 8.3-FPM, Node 20, Composer, pm2, ufw; clones the repo to `/var/www/laravel_next`; configures the nginx vhost; enables systemd units.

### One-time backend + first-build

```bash
# Configure backend env
sudo cp /var/www/laravel_next/deploy/.env.production.example /var/www/laravel_next/backend/.env
sudo nano /var/www/laravel_next/backend/.env   # fill DB_HOST, DB_PASSWORD, APP_URL, CORS_ALLOWED_ORIGINS, SANCTUM_STATEFUL_DOMAINS

# Backend
cd /var/www/laravel_next/backend
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --force
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Client (one-time build; CI takes over after this)
cd /var/www/laravel_next/client
sudo -u www-data npm ci
sudo -u www-data NEXT_PUBLIC_API_URL=http://<elastic-ip> npm run build
sudo -u www-data mkdir -p .next/standalone/.next
sudo -u www-data cp -R .next/static .next/standalone/.next/static
sudo -u www-data cp -R public .next/standalone/public
sudo -u www-data pm2 start .next/standalone/server.js \
  --name laravel_next_client --cwd /var/www/laravel_next/client/.next/standalone
sudo -u www-data pm2 save
```

Smoke test from your laptop:

```bash
curl -sS http://<elastic-ip>/                                 # Next.js HTML
curl -sS -o /dev/null -w '%{http_code}\n' http://<elastic-ip>/api/user   # 401
```

### GitHub repository secrets

Repo → Settings → Secrets and variables → Actions:

| Secret                  | Value                                                       |
| ----------------------- | ----------------------------------------------------------- |
| `EC2_HOST`              | Elastic IP                                                  |
| `EC2_USER`              | `ubuntu`                                                    |
| `EC2_SSH_KEY`           | Full contents of `~/.ssh/laravel-next-ec2.pem`              |
| `NEXT_PUBLIC_API_URL`   | `http://<elastic-ip>`                                       |

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
- [ ] `bootstrap.sh` run on EC2; nginx + PHP-FPM + pm2 startup all active
- [ ] `backend/.env` filled in with RDS endpoint + password; `php artisan migrate --force` succeeded
- [ ] First-build of Next.js running under pm2; `pm2 save` executed
- [ ] Live URL smoke-tested: `/` returns Next.js HTML, `/api/user` returns 401, register → login → dashboard works
- [ ] Four GitHub secrets set in repo
- [ ] `development` merged to `main`; both workflows green; second push deploys without manual intervention
