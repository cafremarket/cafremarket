#!/usr/bin/env bash
# Run on the production server after deploying Cafrepay code.
# Speeds up Laravel by caching config/routes (view:cache is skipped).
#
# Usage:
#   bash deploy/optimize-speed.sh
#   SKIP_COMPOSER=1 bash deploy/optimize-speed.sh          # skip slow dump-autoload
#   RUN_COMPOSER=1 bash deploy/optimize-speed.sh           # force dump-autoload
#   WEB_USER=www WEB_GROUP=www bash deploy/optimize-speed.sh
set -euo pipefail

cd "$(dirname "$0")/.."

WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"
# dump-autoload -o is often slow/hangs on large vendors — off by default
RUN_COMPOSER="${RUN_COMPOSER:-0}"
SKIP_COMPOSER="${SKIP_COMPOSER:-0}"
COMPOSER_TIMEOUT="${COMPOSER_TIMEOUT:-180}"

pct() {
  local n="$1"
  local msg="$2"
  printf '\n[%3d%%] %s\n' "$n" "$msg"
}

spinner_while() {
  local pid="$1"
  local label="$2"
  local frames='|/-\\'
  local i=0
  local start
  start=$(date +%s)
  while kill -0 "$pid" 2>/dev/null; do
    local now elapsed
    now=$(date +%s)
    elapsed=$((now - start))
    printf '\r      %s %s (%ss)   ' "${frames:i++%${#frames}:1}" "$label" "$elapsed"
    sleep 0.2
  done
  printf '\r      done: %s                    \n' "$label"
}

pct 5 "Fixing storage permissions for ${WEB_USER}:${WEB_GROUP}"
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
chown -R "${WEB_USER}:${WEB_GROUP}" storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
find storage/logs -type f -name '*.log' -exec chown "${WEB_USER}:${WEB_GROUP}" {} \; 2>/dev/null || true
find storage/logs -type f -name '*.log' -exec chmod 664 {} \; 2>/dev/null || true
echo "      storage ownership fixed"

pct 25 "Clearing stale caches"
php artisan optimize:clear
echo "      caches cleared"

pct 50 "Caching config"
php artisan config:cache
echo "      config cached"

pct 70 "Caching routes"
php artisan route:cache
echo "      routes cached"

pct 85 "Caching events"
php artisan event:cache 2>/dev/null || echo "      events cache skipped (optional)"

# Do NOT run view:cache / optimize — breaks on laravel-exceptions-renderer components

if [[ "$SKIP_COMPOSER" == "1" ]]; then
  pct 100 "Skipping composer dump-autoload (SKIP_COMPOSER=1)"
elif [[ "$RUN_COMPOSER" == "1" ]]; then
  pct 90 "Rebuilding optimized autoloader (timeout ${COMPOSER_TIMEOUT}s)…"
  echo "      This can take 1–3 minutes. Press Ctrl+C to cancel; site already optimized."
  (
    COMPOSER_PROCESS_TIMEOUT="$COMPOSER_TIMEOUT" \
      composer dump-autoload -o --no-dev -v
  ) &
  composer_pid=$!
  spinner_while "$composer_pid" "composer dump-autoload"
  if wait "$composer_pid"; then
    echo "      autoloader rebuilt"
  else
    echo "      WARNING: composer dump-autoload failed/timed out — config/route cache still OK"
  fi
  pct 100 "Finished"
else
  pct 100 "Finished (composer skipped — already fast enough)"
  echo "      Tip: RUN_COMPOSER=1 bash deploy/optimize-speed.sh  to rebuild autoloader"
fi

echo ""
echo "All set. Prefer: sudo -u ${WEB_USER} php artisan ..."
echo "Do not run: php artisan view:cache  OR  php artisan optimize"
