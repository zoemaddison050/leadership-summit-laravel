#!/usr/bin/env bash
set -euo pipefail

# Switch production from the old live app directory to the git repo directory
# Preserves .env, storage, vendor (or runs composer), updates public_html symlink,
# runs migrations, and clears caches.

PHP_BIN="/usr/local/php83/bin/php"
APP_OLD="${APP_OLD:-$HOME/leadership}"
APP_NEW="${APP_NEW:-$HOME/leadership_summit_lavarel}"
DOCROOT="${DOCROOT:-$HOME/domains/globaleadershipacademy.com/public_html}"

echo "Old app: $APP_OLD"
echo "New app: $APP_NEW"
echo "Docroot: $DOCROOT"

if [[ ! -d "$APP_OLD" ]]; then
  echo "ERROR: Old app directory not found: $APP_OLD" >&2
  exit 1
fi
if [[ ! -d "$APP_NEW" ]]; then
  echo "ERROR: New app directory not found: $APP_NEW" >&2
  exit 1
fi

timestamp() { date +%F_%H%M%S; }
TS="$(timestamp)"

echo "Creating backups..."
mkdir -p "$HOME/backups"
tar -C "$APP_OLD" -czf "$HOME/backups/leadership_old_${TS}.tgz" . || true
if [[ -d "$DOCROOT" && ! -L "$DOCROOT" ]]; then
  tar -C "$DOCROOT" -czf "$HOME/backups/public_html_${TS}.tgz" . || true
fi

echo "Putting app in maintenance mode..."
if [[ -f "$APP_OLD/artisan" ]]; then
  (cd "$APP_OLD" && "$PHP_BIN" artisan down || true)
fi

echo "Syncing .env and storage..."
if [[ -f "$APP_OLD/.env" ]]; then
  cp -n "$APP_OLD/.env" "$APP_NEW/.env" || true
fi

# Prefer rsync if available for faster syncs
if command -v rsync >/dev/null 2>&1; then
  rsync -a "$APP_OLD/storage/app/" "$APP_NEW/storage/app/" || true
  rsync -a "$APP_OLD/public/uploads/" "$APP_NEW/public/uploads/" 2>/dev/null || true
else
  mkdir -p "$APP_NEW/storage/app"
  cp -a "$APP_OLD/storage/app/." "$APP_NEW/storage/app/" 2>/dev/null || true
  mkdir -p "$APP_NEW/public/uploads"
  cp -a "$APP_OLD/public/uploads/." "$APP_NEW/public/uploads/" 2>/dev/null || true
fi

echo "Installing dependencies (composer) or copying vendor..."
if command -v composer >/dev/null 2>&1; then
  (cd "$APP_NEW" && composer install --no-dev --optimize-autoloader)
else
  if [[ -d "$APP_OLD/vendor" ]]; then
    rsync -a "$APP_OLD/vendor/" "$APP_NEW/vendor/" || cp -a "$APP_OLD/vendor/." "$APP_NEW/vendor/"
  else
    echo "WARNING: composer not found and no vendor directory to copy; app may fail until vendor is present."
  fi
fi

echo "Ensuring storage symlink and permissions..."
mkdir -p "$APP_NEW/storage" "$APP_NEW/bootstrap/cache"
chmod -R ug+rwX "$APP_NEW/storage" "$APP_NEW/bootstrap/cache" || true
(cd "$APP_NEW" && "$PHP_BIN" artisan storage:link || true)

echo "Preserving existing built frontend assets (public/build) if present..."
if [[ -d "$DOCROOT/build" ]]; then
  mkdir -p "$APP_NEW/public/build"
  rsync -a "$DOCROOT/build/" "$APP_NEW/public/build/" || cp -a "$DOCROOT/build/." "$APP_NEW/public/build/"
fi

echo "Migrating database..."
(cd "$APP_NEW" && "$PHP_BIN" artisan migrate --force || true)

echo "Updating document root to point to new public/ ..."
# Preserve any ACME/.well-known challenges
WELL_KNOWN_TMP="/tmp/well-known_${TS}"
if [[ -d "$DOCROOT/.well-known" ]]; then
  mkdir -p "$WELL_KNOWN_TMP"
  cp -a "$DOCROOT/.well-known/." "$WELL_KNOWN_TMP/" || true
fi

if [[ -L "$DOCROOT" ]]; then
  ln -sfn "$APP_NEW/public" "$DOCROOT"
else
  mv "$DOCROOT" "${DOCROOT}_old_${TS}" || true
  ln -s "$APP_NEW/public" "$DOCROOT"
fi

if [[ -d "$WELL_KNOWN_TMP" ]]; then
  mkdir -p "$APP_NEW/public/.well-known"
  cp -a "$WELL_KNOWN_TMP/." "$APP_NEW/public/.well-known/" || true
fi

echo "Clearing and warming caches..."
(cd "$APP_NEW" && "$PHP_BIN" artisan optimize:clear)
(cd "$APP_NEW" && "$PHP_BIN" artisan config:cache || true)
(cd "$APP_NEW" && "$PHP_BIN" artisan route:cache || true)

echo "Bringing app up..."
(cd "$APP_NEW" && "$PHP_BIN" artisan up || true)

echo "Done. Verify the site, then remove old directory if desired:"
echo "  rm -rf '$APP_OLD'"
echo "Backups in: $HOME/backups"
