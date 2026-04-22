#!/usr/bin/env bash
set -e
cd "$(dirname "$0")/../httpdocs" 2>/dev/null || cd ../httpdocs || true

echo "[1/6] Composer install"
composer install --no-dev --optimize-autoloader

echo "[2/6] Optimize"
php artisan key:generate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[3/6] Migrate & seed"
php artisan migrate --force
php artisan db:seed --class=WoorkSaaSSeeder --force

echo "[4/6] Queue tables (if database driver)"
php artisan queue:table || true
php artisan migrate --force

echo "[5/6] Permissions"
chmod -R ug+rw storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;

echo "[6/6] Done."
