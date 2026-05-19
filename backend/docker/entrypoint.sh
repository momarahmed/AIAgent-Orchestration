#!/usr/bin/env bash
# -------------------------------------------------------------------
# Laravel 12 dev entrypoint
#   - bootstraps the project on first run (composer create-project)
#   - installs deps, generates app key, runs migrations
#   - then execs the dev server
# -------------------------------------------------------------------
set -e

cd /var/www/html

# Bootstrap a fresh Laravel app if the project is empty / incomplete.
# We do a recursive *no-clobber* merge so any application code we ship
# (controllers, models, migrations, routes) is preserved while missing
# vanilla framework files (config/, public/, bootstrap/, etc.) are filled in.
if [ ! -f "artisan" ] || [ ! -f "bootstrap/app.php" ] || [ ! -f "public/index.php" ]; then
  echo "[entrypoint] Laravel skeleton incomplete — scaffolding Laravel 12 (merge mode)..."
  composer create-project --prefer-dist "laravel/laravel:^12.0" /tmp/laravel
  # Drop the default .env / .env.example coming from create-project so our
  # platform-specific config is always used.
  rm -f /tmp/laravel/.env /tmp/laravel/.env.example
  # Recursive no-clobber copy: keeps our custom files, fills in missing ones.
  cp -Rn /tmp/laravel/. /var/www/html/
  rm -rf /tmp/laravel
fi

# Always ensure our .env reflects the shipped .env.example (preserve APP_KEY
# if one was already generated).
if [ -f ".env.example" ]; then
  if [ -f ".env" ]; then
    EXISTING_KEY="$(grep -E '^APP_KEY=base64:' .env || true)"
  else
    EXISTING_KEY=""
  fi
  cp .env.example .env
  if [ -n "$EXISTING_KEY" ]; then
    sed -i "s|^APP_KEY=.*|$EXISTING_KEY|" .env
  fi
fi

# Install PHP dependencies if vendor missing
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor 2>/dev/null || true)" ]; then
  echo "[entrypoint] Installing composer dependencies..."
  composer install --no-interaction --prefer-dist --no-progress
fi

# Ensure Sanctum is present (asset platform uses token auth)
if ! php -r "exit(file_exists('vendor/laravel/sanctum/composer.json')?0:1);" 2>/dev/null; then
  echo "[entrypoint] Installing Laravel Sanctum..."
  composer require laravel/sanctum:^4.0 --no-interaction --no-progress
fi

# Publish Sanctum migration if not yet present
if ! ls database/migrations/*_create_personal_access_tokens_table.php >/dev/null 2>&1; then
  echo "[entrypoint] Publishing Sanctum config + migrations..."
  php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider" --force || true
fi

# Generate APP_KEY if missing
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
  echo "[entrypoint] Generating APP_KEY..."
  php artisan key:generate --force || true
fi

# Wait for MySQL to be reachable
echo "[entrypoint] Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
for i in $(seq 1 60); do
  if mysqladmin ping -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" --silent 2>/dev/null; then
    echo "[entrypoint] MySQL is up."
    break
  fi
  sleep 1
done

# Run migrations (idempotent)
echo "[entrypoint] Running migrations..."
php artisan migrate --force || true

# Seed if seeders exist (only on first boot, when no data)
if [ -f "database/seeders/DatabaseSeeder.php" ]; then
  if [ ! -f "storage/.seeded" ]; then
    echo "[entrypoint] Running database seeders..."
    php artisan db:seed --force || true
    mkdir -p storage && touch storage/.seeded
  fi
fi

# Permissions
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting Laravel dev server: $*"
exec "$@"
