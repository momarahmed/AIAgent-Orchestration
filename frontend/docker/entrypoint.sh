#!/usr/bin/env bash
# Next.js dev entrypoint — installs deps on first boot and starts the dev server.
set -e

cd /app

if [ ! -f "package.json" ]; then
  echo "[entrypoint] No package.json found in /app — aborting."
  exit 1
fi

if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null || true)" ]; then
  echo "[entrypoint] Installing npm dependencies..."
  npm install --no-audit --no-fund
fi

echo "[entrypoint] Starting Next.js dev server: $*"
exec "$@"
