# Deploy to Railway (PostgreSQL)

## Prerequisites

-   Railway account: https://railway.app
-   Railway CLI: `npm install -g @railway/cli`

## Deployment Steps

### 1. Login and initialize

```bash
railway login
railway init
```

### 2. Add PostgreSQL database

In Railway Dashboard:

-   Click your project
-   Click "New" → "Database" → "Add PostgreSQL"
-   Railway automatically creates these variables:
    -   `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`

### 3. Configure environment variables

In Railway Dashboard → Your web service → Variables, add:

**Required:**

```
APP_NAME=Future Academy
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate locally - see below>
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}

SESSION_DRIVER=database
CACHE_STORE=database
LOG_LEVEL=error
```

**Generate APP_KEY locally:**

```bash
php artisan key:generate --show
```

Copy the output (e.g., `base64:...`) and paste as `APP_KEY` value.

### 4. Deploy

```bash
git add .
git commit -m "Configure for Railway PostgreSQL deployment"
git push origin master
```

Railway will automatically:

-   Detect PHP via Nixpacks
-   Install dependencies via Composer and npm
-   Build assets with Vite
-   Run migrations on startup
-   Start the app with `php artisan serve`

### 5. Verify deployment

-   Check deployment logs in Railway Dashboard
-   Visit your public domain (shown in Dashboard)
-   Login with: `super@admin.com` / `password`

## Troubleshooting

### View logs

```bash
railway logs --follow
```

### Run migrations manually

```bash
railway run php artisan migrate --force
```

### Fix permissions

```bash
railway run chmod -R 775 storage bootstrap/cache
```

### Seed database

```bash
railway run php artisan db:seed --force
```

### Common issues

**"No application encryption key has been specified"**

-   Generate locally: `php artisan key:generate --show`
-   Add to Railway Variables as `APP_KEY`

**Database connection failed**

-   Verify PostgreSQL database is created in Railway
-   Check that `DB_*` variables reference `${PG*}` correctly
-   Ensure the web service and database are in the same project

**App not starting**

-   Check Railway logs for errors
-   Verify all required env vars are set
-   Ensure `composer.lock` is committed

## Cost & Limits

-   Free tier: $5 monthly credit
-   Auto-sleeps when idle
-   500 hours execution time/month
-   1GB PostgreSQL storage included

## Optional: Custom domain

In Railway Dashboard → Settings → Domains:

-   Click "Generate Domain" for free subdomain
-   Or add your custom domain and configure DNS

## Local development with PostgreSQL

If you want to use PostgreSQL locally:

1. Start PostgreSQL locally (via Laragon, Docker, or native)
2. Update `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=future_academy
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

3. Run migrations:

```bash
php artisan migrate:fresh --seed
```
