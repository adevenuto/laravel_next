#!/usr/bin/env bash
#
# bootstrap.sh — one-shot provisioner for the laravel_next app on a fresh
# Ubuntu 22.04 EC2 (t2.micro / t3.micro). Idempotent: safe to re-run.
#
# Usage (from your laptop, SSH'd into the EC2 box as `ubuntu`):
#   curl -fsSL https://raw.githubusercontent.com/adevenuto/laravel_next/development/deploy/bootstrap.sh \
#     | REPO_BRANCH=development bash
#
# After deploy is live on main, drop the env override:
#   curl -fsSL https://raw.githubusercontent.com/adevenuto/laravel_next/main/deploy/bootstrap.sh | bash
#
# Env vars (optional):
#   REPO_URL    — git remote (default: https://github.com/adevenuto/laravel_next.git)
#   REPO_BRANCH — branch to clone (default: main)
#   APP_DIR     — install path (default: /var/www/laravel_next)

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/adevenuto/laravel_next.git}"
REPO_BRANCH="${REPO_BRANCH:-main}"
APP_DIR="${APP_DIR:-/var/www/laravel_next}"
PHP_VERSION="8.3"
NODE_MAJOR="20"

echo "==> Updating apt indexes..."
sudo apt-get update -y

echo "==> Adding ondrej/php PPA (PHP ${PHP_VERSION} for Ubuntu 22.04)..."
sudo apt-get install -y software-properties-common ca-certificates
sudo add-apt-repository -y ppa:ondrej/php
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

echo "==> Installing pm2 globally..."
if ! command -v pm2 >/dev/null 2>&1; then
    sudo npm install -g pm2
fi

echo "==> Creating ${APP_DIR}..."
sudo mkdir -p "${APP_DIR}"

echo "==> Cloning ${REPO_URL} (branch ${REPO_BRANCH})..."
if [[ ! -d "${APP_DIR}/.git" ]]; then
    sudo git clone --branch "${REPO_BRANCH}" "${REPO_URL}" "${APP_DIR}"
else
    echo "    (already a git repo; skipping clone)"
fi
sudo chown -R www-data:www-data "${APP_DIR}"

echo "==> Installing nginx vhost..."
if [[ -f "${APP_DIR}/deploy/nginx.conf" ]]; then
    sudo cp "${APP_DIR}/deploy/nginx.conf" /etc/nginx/sites-available/laravel_next
    sudo ln -sf /etc/nginx/sites-available/laravel_next /etc/nginx/sites-enabled/laravel_next
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

echo "==> Configuring pm2 startup for the ubuntu user..."
# Generate the startup command and run it (it's printed on the last line).
sudo env PATH="$PATH:/usr/bin" pm2 startup systemd -u ubuntu --hp /home/ubuntu \
    | grep -E '^sudo ' | tail -n1 | sudo bash || true

cat <<'EOF'

==============================================================================
✓ Bootstrap complete.

Next steps (Phase 4 — first manual deploy):

  1. Configure backend env:
       sudo cp /var/www/laravel_next/deploy/.env.production.example /var/www/laravel_next/backend/.env
       sudo nano /var/www/laravel_next/backend/.env   # fill in DB_HOST, DB_PASSWORD, APP_URL, CORS_ALLOWED_ORIGINS, SANCTUM_STATEFUL_DOMAINS

  2. Backend bootstrap:
       cd /var/www/laravel_next/backend
       sudo -u www-data composer install --no-dev --optimize-autoloader
       sudo -u www-data php artisan key:generate
       sudo -u www-data php artisan migrate --force
       sudo chown -R www-data:www-data storage bootstrap/cache
       sudo chmod -R 775 storage bootstrap/cache

  3. Client first build + run (one-time; CI/CD takes over after that):
       cd /var/www/laravel_next/client
       sudo -u www-data npm ci
       sudo -u www-data NEXT_PUBLIC_API_URL=http://<elastic-ip> npm run build
       # Stage standalone output where pm2 will run it
       sudo -u www-data mkdir -p .next/standalone/.next
       sudo -u www-data cp -R .next/static .next/standalone/.next/static
       sudo -u www-data cp -R public .next/standalone/public
       sudo -u www-data pm2 start .next/standalone/server.js --name laravel_next_client --cwd .next/standalone
       sudo -u www-data pm2 save

  4. Smoke test from your laptop:
       curl -sS http://<elastic-ip>/             # should return Next.js HTML
       curl -sS -o /dev/null -w '%{http_code}\n' http://<elastic-ip>/api/user   # expect 401

==============================================================================
EOF
