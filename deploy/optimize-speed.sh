#!/usr/bin/env bash
# Run on the production server after deploying Cafrepay code.
# Speeds up Laravel by caching config/routes/views and warming OPcache-friendly artifacts.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Clearing stale caches"
php artisan optimize:clear

echo "==> Caching config / routes / events / views"
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache 2>/dev/null || true

echo "==> Rebuilding package discovery / classmap"
composer dump-autoload -o --no-dev 2>/dev/null || composer dump-autoload -o

echo "==> Done. Recommended production .env values:"
echo "    APP_ENV=production"
echo "    APP_DEBUG=false"
echo "    LOG_LEVEL=error"
echo "    CACHE_DRIVER=redis   # or file if Redis is unavailable"
echo "    SESSION_DRIVER=redis # or file"
echo "    QUEUE_CONNECTION=database  # then run: php artisan queue:work"
echo ""
echo "Also ensure PHP OPcache is enabled in the panel (aapanel → PHP → OPcache)."
