#!/usr/bin/env bash
#
# bootstrap.sh — one-shot provisioner for the laravel_vue app on a fresh
# Ubuntu 22.04 EC2 (t2.micro / t3.micro). Idempotent: safe to re-run.
#
# Usage (from your laptop, SSH'd into the EC2 box as `ubuntu`):
#   curl -fsSL https://raw.githubusercontent.com/adevenuto/laravel_next/development/deploy/bootstrap.sh \
#     | REPO_BRANCH=development bash
#
# After deploy is live on main, drop the env override:
#   curl -fsSL https://raw.githubusercontent.com/adevenuto/laravel_next/main/deploy/bootstrap.sh | bash
#
# (The GitHub repo name is still `laravel_next` even though the app was
# rebuilt as a Vue SPA. App-level naming uses `laravel_vue`.)
#
# Env vars (optional):
#   REPO_URL    — git remote (default: https://github.com/adevenuto/laravel_next.git)
#   REPO_BRANCH — branch to clone (default: main)
#   APP_DIR     — install path (default: /var/www/laravel_vue)

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/adevenuto/laravel_next.git}"
REPO_BRANCH="${REPO_BRANCH:-main}"
APP_DIR="${APP_DIR:-/var/www/laravel_vue}"
# PHP 8.5 ships in Ubuntu 26.04 (Resolute) default repos — no PPA needed.
# For older Ubuntu LTS (22.04/24.04), override with PHP_VERSION=8.3 and add the ondrej/php PPA.
PHP_VERSION="${PHP_VERSION:-8.5}"
NODE_MAJOR="20"

echo "==> Updating apt indexes..."
sudo apt-get update -y

echo "==> Installing nginx, PHP ${PHP_VERSION}, mysql-client, git, build tools..."
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    nginx \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-gd \
    mysql-client \
    git \
    unzip \
    curl \
    rsync \
    ufw

echo "==> Installing Composer..."
if ! command -v composer >/dev/null 2>&1; then
    curl -fsSL https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi
composer --version

echo "==> Installing Node ${NODE_MAJOR} from NodeSource..."
if ! command -v node >/dev/null 2>&1 || ! node -v | grep -q "^v${NODE_MAJOR}\."; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | sudo -E bash -
    sudo apt-get install -y nodejs
fi
node -v && npm -v

echo "==> Creating ${APP_DIR}..."
sudo mkdir -p "${APP_DIR}"

echo "==> Cloning ${REPO_URL} (branch ${REPO_BRANCH})..."
if [[ ! -d "${APP_DIR}/.git" ]]; then
    sudo git clone --branch "${REPO_BRANCH}" "${REPO_URL}" "${APP_DIR}"
else
    echo "    (already a git repo; skipping clone)"
fi

# Ownership model:
#   - backend/: ubuntu:www-data so the deploy user (ubuntu) can rsync/composer/artisan,
#               and PHP-FPM (www-data group) can read everything.
#   - storage/ + bootstrap/cache/: group-writable + setgid so PHP-FPM can persist
#               logs/sessions/cached files, and new files inherit www-data group.
#   - client/: ubuntu:ubuntu — Vite build output is static; nginx (www-data) reads
#               via default world-readable perms.
sudo chown -R ubuntu:www-data "${APP_DIR}"
sudo chown -R ubuntu:ubuntu "${APP_DIR}/client" 2>/dev/null || true
if [[ -d "${APP_DIR}/backend/storage" ]]; then
    sudo chmod -R g+ws "${APP_DIR}/backend/storage" "${APP_DIR}/backend/bootstrap/cache"
fi

echo "==> Installing nginx vhost..."
if [[ -f "${APP_DIR}/deploy/nginx.conf" ]]; then
    sudo cp "${APP_DIR}/deploy/nginx.conf" /etc/nginx/sites-available/laravel_vue
    sudo ln -sf /etc/nginx/sites-available/laravel_vue /etc/nginx/sites-enabled/laravel_vue
    sudo rm -f /etc/nginx/sites-enabled/default
    sudo nginx -t
    sudo systemctl reload nginx
else
    echo "    WARN: ${APP_DIR}/deploy/nginx.conf not found; configure nginx manually after cloning."
fi

echo "==> Enabling firewall (UFW)..."
sudo ufw --force enable
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

echo "==> Enabling system services..."
sudo systemctl enable --now nginx
sudo systemctl enable --now php${PHP_VERSION}-fpm

cat <<'EOF'

==============================================================================
✓ Bootstrap complete.

Next steps (first manual deploy):

  1. Configure backend env:
       sudo cp /var/www/laravel_vue/deploy/.env.production.example /var/www/laravel_vue/backend/.env
       sudo nano /var/www/laravel_vue/backend/.env   # fill in DB_HOST, DB_PASSWORD, APP_URL, CORS_ALLOWED_ORIGINS, SANCTUM_STATEFUL_DOMAINS

  2. Backend bootstrap:
       cd /var/www/laravel_vue/backend
       sudo -u www-data composer install --no-dev --optimize-autoloader
       sudo -u www-data php artisan key:generate
       sudo -u www-data php artisan migrate --force
       sudo chown -R www-data:www-data storage bootstrap/cache
       sudo chmod -R 775 storage bootstrap/cache

  3. Client first build (one-time; CI/CD rsyncs dist/ after that):
       cd /var/www/laravel_vue/client
       sudo -u ubuntu npm ci
       sudo -u ubuntu VITE_API_URL=http://<elastic-ip> npm run build
       # nginx already points at /var/www/laravel_vue/client; dist/ is its root via try_files fallback.

  4. Smoke test from your laptop:
       curl -sS http://<elastic-ip>/             # should return the SPA shell (index.html)
       curl -sS -o /dev/null -w '%{http_code}\n' http://<elastic-ip>/api/user   # expect 401

==============================================================================
EOF
