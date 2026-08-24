#!/usr/bin/env bash
set -e

# Attendre que Postgres accepte les connexions
if [ -n "$DB_HOST" ]; then
  echo "Attente de PostgreSQL (${DB_HOST}:${DB_PORT:-5432})..."
  until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 1
  done
  echo "PostgreSQL est prêt."
fi

# Uniquement le conteneur principal "app" doit gérer les caches/migrations
if [ "${APP_ROLE:-app}" = "app" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
  fi
fi

exec "$@"
