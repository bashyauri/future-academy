#!/usr/bin/env bash
set -e

# Create SQLite file on mounted volume
touch /app/database/database.sqlite || true

# Clear cached config/routes/views (safe in container)
php artisan config:clear || true

# Run migrations
php artisan migrate --force || true

# Serve Laravel app
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
