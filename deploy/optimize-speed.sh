#!/usr/bin/env bash
# Run on the production server after deploying Cafrepay code.
# Speeds up Laravel by caching config/routes (view:cache is skipped — it breaks on
# laravel-exceptions-renderer Blade components in this stack).
set -euo pipefail

cd "$(dirname "$0")/.."

# aapanel PHP-FPM user (change if your panel uses nginx/nobody)
WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"

echo "==> Fixing storage permissions for ${WEB_USER}:${WEB_GROUP}"
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
chown -R "${WEB_USER}:${WEB_GROUP}" storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
# Daily log often created as root by artisan — force web user ownership
find storage/logs -type f -name '*.log' -exec chown "${WEB_USER}:${WEB_GROUP}" {} \;
find storage/logs -type f -name '*.log' -exec chmod 664 {} \;

echo "==> Clearing stale caches"
php artisan optimize:clear

echo "==> Caching config / routes / events"
php artisan config:cache
php artisan route:cache
php artisan event:cache 2>/dev/null || true

# Do NOT run: php artisan view:cache  OR  php artisan optimize
# Those compile vendor exception views and fail with:
#   Unable to locate a class or view for component [laravel-exceptions-renderer::card]

echo "==> Rebuilding optimized autoloader"
composer dump-autoload -o --no-dev 2>/dev/null || composer dump-autoload -o

echo "==> Done."
echo "    Prefer: sudo -u ${WEB_USER} php artisan ...  (avoid running artisan as root)"
