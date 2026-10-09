#!/usr/bin/env bash
# Reset the whole marketplace but keep categories, the admin user and platform settings.
#
#   scripts/reset-keep-categories.sh --dry-run     # preview only
#   scripts/reset-keep-categories.sh               # backup DB + uploads, then reset (asks to confirm)
#   scripts/reset-keep-categories.sh --admin-id=1  # keep a different admin
#
# Backups go to storage/backups/<timestamp>/ — restore with:
#   docker exec -i cafremarket-mysql mysql -uroot -proot cafremarket < storage/backups/<ts>/cafremarket.sql
#   tar -xzf storage/backups/<ts>/storage-public.tgz -C storage/app
set -euo pipefail

APP="$(cd "$(dirname "$0")/.." && pwd)"
DB_CONTAINER="${DB_CONTAINER:-cafremarket-mysql}"
DB_NAME="${DB_NAME:-cafremarket}"

ARGS=()
DRY_RUN=0
for a in "$@"; do
  [[ "$a" == "--dry-run" ]] && DRY_RUN=1
  ARGS+=("$a")
done

cd "$APP"

if [[ $DRY_RUN -eq 1 ]]; then
  php artisan cafrepay:reset-keep-categories "${ARGS[@]}"
  exit 0
fi

echo "This deletes ALL shops, merchants, customers, products, orders, wallets and chats"
echo "in database '$DB_NAME'. Categories, the admin user and platform settings are kept."
read -r -p "Type RESET to continue: " answer
[[ "$answer" == "RESET" ]] || { echo "Cancelled."; exit 1; }

BACKUP="$APP/storage/backups/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
echo "Backing up database to $BACKUP/cafremarket.sql ..."
docker exec "$DB_CONTAINER" mysqldump -uroot -proot --single-transaction --routines "$DB_NAME" \
  > "$BACKUP/cafremarket.sql" 2>/dev/null
[[ -s "$BACKUP/cafremarket.sql" ]] || { echo "Backup failed — aborting."; exit 1; }
echo "Backing up uploads to $BACKUP/storage-public.tgz ..."
tar -czf "$BACKUP/storage-public.tgz" -C "$APP/storage/app" public

php artisan cafrepay:reset-keep-categories --force "${ARGS[@]}"

echo
echo "Done. Backup kept at: $BACKUP"
