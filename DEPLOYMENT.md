# Railway Deployment Guide for Future Academy

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

#### Option A: Using Railway Dashboard (Easiest)

1. Go to https://railway.app
2. Click "Start a New Project"
3. Select "Deploy from GitHub repo"
4. Authorize Railway to access your GitHub
5. Select the `future-academy` repository
6. Railway will auto-detect Laravel and start building

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

In Railway Dashboard > Variables, add:

```
APP_NAME=Future Academy
APP_ENV=production
APP_KEY=base64:Tqk2YnAGUSwaMmJl1AoS7RP8qlst/Jut+N0ljDiO1+Y=
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
LOG_LEVEL=error
```

**Important:** Railway auto-provides `${{RAILWAY_PUBLIC_DOMAIN}}` variable.

### 4. Create Persistent Volume for SQLite

In Railway Dashboard:

1. Go to your service
2. Click "Volumes" tab
3. Click "New Volume"
4. Name: `database`
5. Mount Path: `/app/database`
6. Click "Add"

### 5. Generate New APP_KEY (Important!)

```bash
# Generate locally
php artisan key:generate --show

# Copy the output and update APP_KEY in Railway variables
```

### 6. Run Migrations & Seeders

Railway automatically runs:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Or manually via Railway CLI:

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

### SQLite Persistence

✅ Your database is persistent thanks to the volume mount
✅ Data survives deployments and restarts
⚠️ Limited to 1 instance (can't scale horizontally)

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

```bash
# Download SQLite file
railway run cat database/database.sqlite > backup.sqlite

# Or use Railway CLI to copy files
railway volume download database database/database.sqlite backup.sqlite
```

### Restore Database

```bash
railway volume upload database backup.sqlite database/database.sqlite
```

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
-   GitHub Issues: Your repository issues page
