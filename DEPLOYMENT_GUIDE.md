# Production Deployment Guide

## Pre-Deployment Checklist

### 1. Environment Setup

```bash
# Copy production environment file
cp .env.production .env

# Update production-specific values
# - APP_URL: Change to your production domain
# - DB_HOST, DB_USERNAME, DB_PASSWORD: Production database credentials
# - REDIS_HOST, REDIS_PASSWORD: Production Redis configuration
# - MAIL_* settings: Configure email service
# - SENTRY_LARAVEL_DSN: Error tracking (optional)

# Verify critical settings
grep -E "APP_ENV|APP_DEBUG|CACHE_STORE|SESSION_DRIVER" .env
```

### 2. Database Configuration

```bash
# Connect to production database
mysql -h your-db-host -u your-username -p

# Create database
CREATE DATABASE future_academy_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Run migrations
php artisan migrate --force

# Seed data if needed
php artisan db:seed --class=ProductionSeeder
```

### 3. Redis Setup

```bash
# Start Redis server
sudo systemctl start redis-server

# Test connection
redis-cli ping

# Verify configuration
redis-cli CONFIG GET maxmemory
redis-cli CONFIG GET maxmemory-policy

# Monitor Redis in production
redis-cli --stat --interval 1
```

### 4. Cache Initialization

```bash
# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Test cache
php artisan tinker
> Cache::put('test', 'working', 60)
> Cache::get('test')
```

### 5. Web Server Configuration

#### Nginx

```bash
# Copy production nginx config
sudo cp nginx.production.conf /etc/nginx/sites-available/future-academy

# Enable the site
sudo ln -s /etc/nginx/sites-available/future-academy /etc/nginx/sites-enabled/

# Test nginx configuration
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx
```

#### PHP-FPM

```bash
# Verify PHP-FPM is running
sudo systemctl status php8.3-fpm

# Adjust pool settings in /etc/php/8.3/fpm/pool.d/www.conf
# Recommended for high traffic:
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.process_idle_timeout = 30s

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

### 6. SSL Certificate Setup

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot certonly --nginx -d future-academy.com -d www.future-academy.com

# Auto-renewal (every 60 days)
sudo systemctl enable certbot.timer
```

### 7. Security Hardening

```bash
# Set proper file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache

# Protect sensitive files
chmod 600 .env
chmod 600 .env.production

# Verify .gitignore includes sensitive files
cat .gitignore | grep -E "\.env|storage/|vendor/"
```

---

## Deployment Process

### Step 1: Code Deployment

```bash
# Option A: Using Git
cd /var/www/future-academy
git pull origin main
git checkout production-ready-tag

# Option B: Using Manual Upload
# Upload application files to /var/www/future-academy
# Ensure proper ownership
sudo chown -R www-data:www-data /var/www/future-academy
```

### Step 2: Dependency Installation

```bash
composer install --optimize-autoloader --no-dev

npm install --production
npm run build
```

### Step 3: Database Migrations

```bash
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

### Step 4: Cache Warming

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Step 5: Health Check

```bash
# Test the application
curl -I https://future-academy.com/health

# Check error logs
tail -f storage/logs/laravel.log

# Verify database connection
php artisan tinker
> DB::connection()->getPdo();
```

---

## Monitoring & Logging

### Application Logging

```php
// config/logging.php
'stack' => [
    'channels' => ['single', 'sentry'],
],

'sentry' => [
    'driver' => 'sentry',
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'level' => 'error',
]
```

### Error Tracking with Sentry

```bash
# 1. Create Sentry account at sentry.io
# 2. Create project for Future Academy
# 3. Get DSN from project settings
# 4. Add to .env: SENTRY_LARAVEL_DSN=https://...@sentry.io/...
# 5. Install Sentry package
composer require sentry/sentry-laravel
```

### Performance Monitoring

```bash
# Install New Relic (optional)
sudo apt-get install newrelic-php5

# Or install Datadog agent
DD_AGENT_MAJOR_VERSION=7 bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_agent.sh)"
```

---

## Backup Strategy

### Daily Database Backups

```bash
#!/bin/bash
# /usr/local/bin/backup-database.sh

BACKUP_DIR="/backups/database"
DATE=$(date +%Y-%m-%d-%H-%M-%S)

mkdir -p $BACKUP_DIR

mysqldump \
  -h localhost \
  -u backup_user \
  -p'backup_password' \
  --all-databases \
  --single-transaction \
  --quick \
  | gzip > "$BACKUP_DIR/database-$DATE.sql.gz"

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/database-$DATE.sql.gz"
```

### Cron Job

```bash
# Add to crontab
0 2 * * * /usr/local/bin/backup-database.sh

# Verify
crontab -l
```

---

## Load Testing

```bash
# Test with 50 concurrent users for 5 minutes
php load-test.php 50 300

# Results are saved to load-test-results-YYYY-MM-DD-HH-MM-SS.json
```

Expected performance:
- ✅ >10 req/s: Excellent (can handle 1000+ concurrent users)
- ⚠️ 5-10 req/s: Good (can handle 500-1000 concurrent users)  
- ❌ <5 req/s: Poor (performance issues need investigation)

---

## Troubleshooting

### High Memory Usage

```bash
# Check Redis memory
redis-cli INFO memory

# Increase memory limit if needed
redis-cli CONFIG SET maxmemory 1gb

# Or enable LRU eviction
redis-cli CONFIG SET maxmemory-policy allkeys-lru
```

### Slow Response Times

```bash
# Check slow queries
tail -f /var/log/mysql/slow.log

# Verify indexes
php artisan tinker
> DB::table('questions')->where('is_mock', 0)->count()

# Check cache hit rate
redis-cli INFO stats | grep hits
```

### High CPU Usage

```bash
# Check top processes
top -b -n 1 | head -20

# Analyze PHP processes
ps aux | grep php

# Check PHP-FPM status
php-fpm -m
```

---

## Rollback Plan

### If Deployment Fails

```bash
# Revert code to previous version
git revert HEAD

# Or restore from backup
git checkout previous-stable-tag

# Reload application
php artisan cache:clear
sudo systemctl reload php8.3-fpm

# Verify
curl https://future-academy.com/health
```

### Database Rollback

```bash
# Restore from backup
gunzip < /backups/database/database-YYYY-MM-DD.sql.gz | mysql

# Or use migrations
php artisan migrate:rollback
```

---

## Post-Deployment

### Verification

```bash
# Run deployment checklist
bash deployment-checklist.sh

# Monitor performance
bash monitor-performance.sh

# Check error logs
tail -50 storage/logs/laravel.log
```

### Performance Benchmarks

Record baseline metrics:
- Response time for practice quiz: < 2 seconds
- Cache hit rate: > 90%
- Database queries per request: < 5
- Memory per user session: < 10MB

---

## Support & Emergency Contacts

- **Database Support**: your-dba@company.com
- **Infrastructure**: your-devops@company.com  
- **Emergency Line**: +1-XXX-XXX-XXXX

---

## Appendix: Configuration Files

### PHP.ini Production Settings

```ini
; /etc/php/8.3/fpm/php.ini
max_execution_time = 60
max_input_time = 60
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
display_errors = Off
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php-fpm.log
```

### MySQL Production Settings

```ini
; /etc/mysql/mysql.conf.d/mysqld.cnf
max_connections = 200
max_allowed_packet = 256M
default_storage_engine = InnoDB
innodb_buffer_pool_size = 4G
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Nginx Performance Settings

Already included in `nginx.production.conf`:
- Gzip compression
- Client request timeouts
- Worker processes
- Connection limits
- Caching headers
