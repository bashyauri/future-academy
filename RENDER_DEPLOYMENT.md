# Deploy to Render (PostgreSQL)

## Prerequisites

-   Render account: https://render.com
-   GitHub repo connected

## One-click deploy (recommended)

1. Push your latest changes to GitHub.
2. In Render, click "New +" → "Blueprint".
3. Select your repo and choose `render.yaml`.
4. Render will create:
    - Web service: `future-academy-web` (Docker)
    - PostgreSQL database: `future-academy-db`
5. After creation, set secrets:
    - `APP_KEY`: generate locally and paste (see below).

## Generate and set APP_KEY

```bash
php artisan key:generate --show
```

Copy the output and add it to the web service Environment as `APP_KEY`.

## Build & Start

-   Dockerfile builds PHP 8.3, Composer deps, Vite assets.
-   Procfile runs `php artisan serve` on `$PORT`.

## Verify

-   Visit the web service URL (e.g., `https://future-academy-web.onrender.com`).
-   If migrations are needed, run once from the Render Shell:

```bash
php artisan migrate --force
```

## Logs & Troubleshooting

-   Check web service logs in Render Dashboard.
-   Common issues:
    -   Missing `APP_KEY` → set it.
    -   DB connection errors → ensure env vars populated via `fromDatabase` in `render.yaml`.
    -   Permissions → run `chmod -R 775 storage bootstrap/cache` via Shell.

## Notes

-   Free plan sleeps when idle; cold start may take a few seconds.
-   You can add `APP_URL` to match the actual Render hostname.
