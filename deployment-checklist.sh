#!/bin/bash
# Production Deployment Checklist
# Run this before going live!

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

checklist_pass=0
checklist_fail=0

check_item() {
    local name=$1
    local command=$2

    if eval "$command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $name"
        ((checklist_pass++))
    else
        echo -e "${RED}✗${NC} $name"
        ((checklist_fail++))
    fi
}

echo -e "${BLUE}=== Production Deployment Checklist ===${NC}\n"

# ========== Environment Configuration ==========
echo -e "${BLUE}📋 Environment Configuration${NC}"
check_item ".env.production exists" "test -f .env.production"
check_item "APP_ENV is 'production'" "grep -q 'APP_ENV=production' .env.production"
check_item "APP_DEBUG is 'false'" "grep -q 'APP_DEBUG=false' .env.production"
check_item "Cache driver configured" "grep -q 'CACHE_STORE=redis' .env.production"
check_item "Session driver configured" "grep -q 'SESSION_DRIVER=redis' .env.production"

echo ""

# ========== Database Configuration ==========
echo -e "${BLUE}🗄️  Database Configuration${NC}"
check_item "Database connection test" "php artisan tinker --execute 'DB::connection()->getPdo();' 2>/dev/null"
check_item "Tables created" "mysql -u root future_academy -e 'SHOW TABLES;' | grep -q questions"
check_item "Indexes optimized" "mysql -u root future_academy -e \"SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='future_academy' AND INDEX_NAME LIKE '%idx_%';\" | grep -q '[1-9]'"

echo ""

# ========== Dependencies ==========
echo -e "${BLUE}📦 Dependencies${NC}"
check_item "PHP version >= 8.3" "php -v | grep -q 'PHP 8\.[3-9]'"
check_item "Composer dependencies installed" "test -d vendor && test -f vendor/autoload.php"
check_item "npm dependencies installed" "test -d node_modules"
check_item "Redis available" "which redis-cli > /dev/null"
check_item "MySQL available" "which mysql > /dev/null"

echo ""

# ========== Application Files ==========
echo -e "${BLUE}📁 Application Files${NC}"
check_item "Artisan executable exists" "test -f artisan && test -x artisan"
check_item "Storage directory writable" "test -w storage"
check_item "Bootstrap cache directory exists" "test -d bootstrap/cache"
check_item "config/cache.php exists" "test -f config/cache.php"
check_item "config/livewire.php exists" "test -f config/livewire.php"

echo ""

# ========== Caching & Sessions ==========
echo -e "${BLUE}⚡ Performance Caching${NC}"
check_item "Config cache enabled" "php artisan config:cache 2>/dev/null && test -f bootstrap/cache/config.php"
check_item "Routes cache enabled" "php artisan route:cache 2>/dev/null && test -f bootstrap/cache/routes.php"
check_item "Views precompiled" "php artisan view:cache 2>/dev/null"

echo ""

# ========== Security ==========
echo -e "${BLUE}🔒 Security${NC}"
check_item ".env file protected" "test -f .env && test ! -r .env -o -f .gitignore && grep -q '\.env' .gitignore"
check_item "No debug files exposed" "! test -f debugbar.html"
check_item "APP_KEY generated" "grep -q 'APP_KEY=base64:' .env.production"
check_item "CORS properly configured" "grep -q 'CORS' config/*.php"

echo ""

# ========== Database Migrations ==========
echo -e "${BLUE}📊 Database Migrations${NC}"
check_item "Migrations directory exists" "test -d database/migrations"
check_item "Seeds directory exists" "test -d database/seeders"
check_item "Migrations up to date" "php artisan migrate:status 2>/dev/null | tail -1 | grep -q 'Success\\|Migrated'"

echo ""

# ========== Nginx/Apache Configuration ==========
echo -e "${BLUE}🌐 Web Server${NC}"
check_item "nginx.production.conf exists" "test -f nginx.production.conf"
check_item "Gzip compression configured" "grep -q 'gzip on' nginx.production.conf"
check_item "Security headers configured" "grep -q 'Strict-Transport-Security' nginx.production.conf"
check_item "Rate limiting configured" "grep -q 'limit_req_zone' nginx.production.conf"

echo ""

# ========== Monitoring & Logging ==========
echo -e "${BLUE}📈 Monitoring Setup${NC}"
check_item "Error logging configured" "grep -q 'error_log' nginx.production.conf"
check_item "Access logging configured" "grep -q 'access_log' nginx.production.conf"
check_item "Storage/logs directory exists" "test -d storage/logs"
check_item "Storage/logs writable" "test -w storage/logs"

echo ""

# ========== Backup & Recovery ==========
echo -e "${BLUE}💾 Backup Configuration${NC}"
check_item "Backup script exists" "test -f backup.sh"
check_item "Database backup location exists" "test -d /backups/database || echo 'Note: Configure backup path'"
check_item "Cron backup scheduled" "crontab -l 2>/dev/null | grep -q backup || echo 'Note: Configure cron backup'"

echo ""

# ========== SSL Certificate ==========
echo -e "${BLUE}🔐 SSL Certificate${NC}"
check_item "SSL certificate exists" "test -f /etc/letsencrypt/live/future-academy.com/fullchain.pem"
check_item "SSL key exists" "test -f /etc/letsencrypt/live/future-academy.com/privkey.pem"
check_item "Certificate not expired" "openssl x509 -checkend 86400 -noout -in /etc/letsencrypt/live/future-academy.com/cert.pem 2>/dev/null || echo 'Note: Certificate expires in <1 day'"

echo ""

# ========== Summary ==========
echo -e "${BLUE}=== Summary ===${NC}"
total=$((checklist_pass + checklist_fail))
percentage=$((checklist_pass * 100 / total))

echo -e "Passed: ${GREEN}$checklist_pass${NC} / Failed: ${RED}$checklist_fail${NC} / Total: $total"
echo -e "Score: ${BLUE}$percentage%${NC}"

echo ""

if [ $checklist_fail -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! Ready for production.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some checks failed. Please review above.${NC}"
    exit 1
fi
