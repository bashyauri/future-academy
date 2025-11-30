# Railway Deployment (Nixpacks + PostgreSQL)

## Prerequisites

-   GitHub account
-   Railway account (https://railway.app)
-   Git installed locally

## Deployment Steps

### 1. Push to GitHub

```bash
git add .
git commit -m "Prepare for Railway deployment"
git push origin master
```

### 2. Deploy on Railway

#### Option A: Using Railway Dashboard (Recommended)

1. Go to https://railway.app
2. Start a New Project → Deploy from GitHub
3. Select the `future-academy` repository
4. In Service Settings → Builder, choose "Nixpacks"
5. Deploy

#### Option B: Using Railway CLI

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login to Railway
railway login

# Initialize project
railway init

# Deploy
railway up

# Open deployed app
railway open
```

### 3. Configure Environment Variables

In Railway Dashboard → Variables, set:

```
APP_NAME=Future Academy
APP_ENV=production
APP_KEY=base64:...       # generate below
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
DB_CONNECTION=pgsql
DB_HOST=${{PGHOST}}
DB_PORT=${{PGPORT}}
DB_DATABASE=${{PGDATABASE}}
DB_USERNAME=${{PGUSER}}
DB_PASSWORD=${{PGPASSWORD}}
SESSION_DRIVER=database
CACHE_STORE=database
LOG_LEVEL=error
```

Notes:

-   Railway injects `PG*` variables when you add a PostgreSQL database resource and link it to the service.
-   `RAILWAY_PUBLIC_DOMAIN` is provided automatically.

### 4. Add PostgreSQL

1. In your Railway project, add a new resource → PostgreSQL.
2. Link the PostgreSQL resource to your service ("Connect" in the Variables panel).
3. Confirm `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD` variables appear on the service.

### 5. Generate New APP_KEY (Important!)

```bash
# Generate locally
php artisan key:generate --show

# Copy the output and update APP_KEY in Railway variables
```

### 6. Run Migrations & Seeders

Migrations are run by the start command in `nixpacks.toml`. You can also run manually:

```bash
railway run php artisan migrate:fresh --seed --force
```

### 7. Create Super Admin

After first deployment:

```bash
railway run php artisan tinker --execute="
\$user = App\Models\User::where('email', 'super@admin.com')->first();
if (!\$user) {
    \$user = App\Models\User::create([
        'name' => 'Super Admin',
        'email' => 'super@admin.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    \$user->assignRole('super-admin');
    echo 'Super admin created!';
}
"
```

## Accessing Your App

-   **Public URL:** https://your-app-name.up.railway.app
-   **Admin Login:**
    -   Email: super@admin.com
    -   Password: password (change this immediately!)

## Important Notes

### Builder & PHP Version

-   Builder is Nixpacks (no Docker required).
-   `railway.json` and `nixpacks.toml` force PHP 8.3 and install Composer (`php83Packages.composer`).

### Database

-   PostgreSQL is recommended and configured via environment variables.
-   No volumes required; managed by Railway.

### File Storage

-   Use Railway volumes or external storage (S3) for user uploads
-   Default `storage/app` is ephemeral

### Custom Domain

1. Go to Railway Dashboard > Settings
2. Click "Generate Domain" for free railway.app subdomain
3. Or add custom domain in "Domains" section

### Logs

```bash
# View logs
railway logs

# Follow logs
railway logs --follow
```

### Redeploy

```bash
# Automatic: Push to GitHub triggers deployment
git push origin master

# Manual: Force redeploy in Railway Dashboard
# Or via CLI:
railway up --detach
```

## Troubleshooting

### Database Not Persisting

-   Verify volume is mounted to `/app/database`
-   Check volume is created and attached

### Build Fails

```bash
# Check build logs in Railway Dashboard
# Common issues:
# - Missing composer.json/composer.lock
# - Node version mismatch
# - Missing .env variables
```

### App Not Loading

-   Check APP_URL matches Railway domain
-   Verify APP_KEY is set
-   Check logs: `railway logs`
-   Ensure migrations ran successfully

### Permission Issues

```bash
railway run chmod -R 775 storage bootstrap/cache
```

## Cost Estimate

**Free Tier:**

-   $5 monthly credit (renews)
-   ~500 hours execution time
-   Includes 1GB persistent volume
-   Perfect for development/testing

**If you exceed free tier:**

-   Pay as you go ($0.000463/GB-hour for compute)
-   Volume storage: $0.25/GB/month

## Maintenance

### Update Dependencies

```bash
composer update
npm update
git add . && git commit -m "Update dependencies"
git push
```

### Clear Cache

```bash
railway run php artisan cache:clear
railway run php artisan config:clear
railway run php artisan route:clear
railway run php artisan view:clear
```

### Backup Database

Use Railway PostgreSQL connection string with your preferred tool (pg_dump/pg_restore or Railway Data tab).

## Security Recommendations

1. **Change default password** immediately after deployment
2. **Set APP_DEBUG=false** in production
3. **Use strong APP_KEY** (generated, not shared)
4. **Enable HTTPS** (Railway provides by default)
5. **Regular backups** of database
6. **Monitor logs** for suspicious activity

## Support

-   Railway Docs: https://docs.railway.app
-   Railway Discord: https://discord.gg/railway
-   GitHub Issues: your repository issues page
