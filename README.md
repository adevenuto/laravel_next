# Next + Laravel

Monorepo with a Next.js frontend (`client/`) and a Laravel backend (`backend/`). Deploys to Hostinger via GitHub Actions and SSH.

## Stack

- **Frontend**: Next.js 15 (App Router) + TypeScript + Tailwind + shadcn/ui
- **Backend**: Laravel 11 + Sanctum (token auth) + MySQL
- **CI/CD**: GitHub Actions → SSH to Hostinger

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
│   │   │       ├── dashboard/page.tsx           → /dashboard
│   │   │       └── my/
│   │   │           ├── portfolio/page.tsx       → /my/portfolio (placeholder)
│   │   │           └── blog/page.tsx            → /my/blog (placeholder)
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
| `/my/portfolio`    | Yes  | Portfolio area (placeholder)             |
| `/my/blog`         | Yes  | Blog area (placeholder)                  |

## Deploying to Hostinger

### One-time Hostinger setup

1. **Enable SSH access** in hPanel → Advanced → SSH Access. Note the host, port, username.
2. **Generate an SSH keypair** locally and add the public key to Hostinger:
   ```bash
   ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/hostinger_deploy
   # Paste hostinger_deploy.pub into Hostinger → SSH → Manage SSH Keys
   ```
3. **Create the MySQL database** in hPanel → Databases. Note db name, user, password, host.
4. **Choose folder layout** on Hostinger (example):
   - Backend: `/home/<user>/domains/api.yourdomain.com/public_html`
   - Client: `/home/<user>/domains/yourdomain.com/next-app`
5. **Point the API subdomain** (e.g. `api.yourdomain.com`) at the backend's `public/` folder. For Laravel on shared hosting, use a `.htaccess` redirect or symlink so `public_html` serves `backend/public/`.
6. **Install Node + pm2** on Hostinger (VPS plans only — shared hosting cannot run Next.js standalone).

### GitHub repository secrets

In your repo → Settings → Secrets and variables → Actions, add:

| Secret                    | Example value                                       |
| ------------------------- | --------------------------------------------------- |
| `HOSTINGER_SSH_HOST`      | `123.45.67.89` or `yourdomain.com`                  |
| `HOSTINGER_SSH_PORT`      | `65002` (Hostinger default) or `22`                 |
| `HOSTINGER_SSH_USER`      | `u123456789`                                        |
| `HOSTINGER_SSH_KEY`       | contents of `~/.ssh/hostinger_deploy` (private key) |
| `HOSTINGER_BACKEND_PATH`  | `/home/u123456789/domains/api.yourdomain.com/backend` |
| `HOSTINGER_CLIENT_PATH`   | `/home/u123456789/domains/yourdomain.com/next-app`  |
| `NEXT_PUBLIC_API_URL`     | `https://api.yourdomain.com`                        |

### One-time server setup (after first deploy)

SSH into Hostinger and create `backend/.env` (the workflow excludes `.env` from rsync):

```bash
ssh -p 65002 u123456789@yourdomain.com
cd /home/u123456789/domains/api.yourdomain.com/backend

cp .env.example .env
php artisan key:generate
nano .env   # set DB_*, APP_URL, CORS_ALLOWED_ORIGINS=https://yourdomain.com
php artisan migrate --force
```

For the client (VPS):

```bash
cd /home/u123456789/domains/yourdomain.com/next-app
pm2 start server.js --name next-laravel-client
pm2 save
pm2 startup
```

### Trigger a deploy

```bash
git push origin main
```

Both workflows run independently and only fire when files in their folder change.

## Pre-deployment checklist

- [ ] SSH keypair generated; public key added to Hostinger; private key in `HOSTINGER_SSH_KEY` secret
- [ ] All seven GitHub secrets set
- [ ] Hostinger MySQL database created; credentials ready
- [ ] DNS / subdomains pointing where you want them
- [ ] `backend/.env` created on the server (db creds, `APP_KEY`, `CORS_ALLOWED_ORIGINS`)
- [ ] First-run migrations executed (`php artisan migrate --force`)
- [ ] (VPS only) Node ≥ 20 + pm2 installed for the Next.js app
- [ ] Web server pointed at `backend/public/` for the API
- [ ] Test push to `main` and watch the Actions tab
