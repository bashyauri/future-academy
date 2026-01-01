#!/bin/bash
# Performance Monitoring Script for Future Academy
# Run with: chmod +x monitor-performance.sh && ./monitor-performance.sh

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Future Academy Performance Monitor ===${NC}\n"

# ========== System Health ==========
echo -e "${BLUE}📊 System Health${NC}"
echo -e "CPU Usage:"
top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{printf "  Available: %.2f%%\n", $1}'

echo -e "Memory Usage:"
free -h | grep Mem | awk '{printf "  Used: %s / %s (%.2f%%)\n", $3, $2, ($3/$2)*100}'

echo -e "Disk Usage:"
df -h / | tail -1 | awk '{printf "  Used: %s / %s (%s)\n", $3, $2, $5}'

echo ""

# ========== Redis Health ==========
echo -e "${BLUE}🔴 Redis Status${NC}"
redis_info=$(redis-cli -p 6379 INFO stats 2>/dev/null)
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Redis is running${NC}"
    echo -e "$redis_info" | grep -E "total_commands_processed|connected_clients" | while read line; do
        echo "  $line"
    done
else
    echo -e "${RED}✗ Redis is DOWN${NC}"
fi

echo ""

# ========== MySQL Health ==========
echo -e "${BLUE}🗄️  MySQL Status${NC}"
mysql_status=$(mysql -u root -e "SELECT VERSION();" 2>/dev/null)
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ MySQL is running${NC}"
    echo "  Version: $(mysql -u root -e 'SELECT VERSION();' 2>/dev/null | tail -1)"

    # Check query cache (if enabled)
    echo ""
    echo "  Database Stats:"
    mysql -u root future_academy -e "
        SELECT
            table_name,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
        FROM information_schema.tables
        WHERE table_schema = 'future_academy'
        ORDER BY (data_length + index_length) DESC
        LIMIT 5;
    " 2>/dev/null | while read line; do
        [ ! -z "$line" ] && echo "    $line"
    done
else
    echo -e "${RED}✗ MySQL is DOWN${NC}"
fi

echo ""

# ========== Laravel Health ==========
echo -e "${BLUE}⚙️  Laravel Application${NC}"
if [ -f "artisan" ]; then
    echo -e "${GREEN}✓ Laravel installation found${NC}"

    # Check cache status
    cache_status=$(php artisan cache:clear 2>&1)
    echo "  Cache: Cleared"

    # Check logs for errors (last hour)
    error_count=$(grep -c "ERROR\|CRITICAL" storage/logs/laravel.log 2>/dev/null | head -20)
    if [ -z "$error_count" ] || [ "$error_count" = "0" ]; then
        echo -e "  Errors (last hour): ${GREEN}None${NC}"
    else
        echo -e "  Errors (last hour): ${RED}$error_count${NC}"
        grep "ERROR\|CRITICAL" storage/logs/laravel.log 2>/dev/null | tail -3 | while read line; do
            echo "    - $(echo $line | cut -c1-80)..."
        done
    fi
else
    echo -e "${RED}✗ Laravel not found${NC}"
fi

echo ""

# ========== Network Health ==========
echo -e "${BLUE}🌐 Network${NC}"
echo "Checking DNS:"
dig future-academy.com +short 2>/dev/null | head -1 | xargs -I {} echo "  IP: {}"

echo -e "HTTP Status:"
http_code=$(curl -s -o /dev/null -w "%{http_code}" https://future-academy.com/health 2>/dev/null)
if [ "$http_code" = "200" ]; then
    echo -e "  ${GREEN}✓ API Health Check: OK${NC}"
else
    echo -e "  ${RED}✗ API Health Check: $http_code${NC}"
fi

echo ""

# ========== Queue Status ==========
echo -e "${BLUE}📬 Queue Status${NC}"
queue_jobs=$(php artisan queue:count 2>/dev/null)
echo "  Pending Jobs: $queue_jobs"

echo ""

# ========== Performance Metrics ==========
echo -e "${BLUE}📈 Performance Metrics${NC}"

# Check recent slow queries
echo "Recent Slow Queries (>1 second):"
mysql -u root future_academy -e "
    SELECT
        sql_text,
        lock_time,
        rows_examined,
        rows_sent
    FROM mysql.slow_log
    ORDER BY query_time DESC
    LIMIT 5;
" 2>/dev/null | tail -10 | while read line; do
    echo "  $line"
done

echo ""

# ========== SSL Certificate ==========
echo -e "${BLUE}🔒 SSL Certificate${NC}"
expiry_date=$(openssl x509 -enddate -noout -in /etc/letsencrypt/live/future-academy.com/cert.pem 2>/dev/null | cut -d= -f2)
if [ ! -z "$expiry_date" ]; then
    days_until=$((($(date -d "$expiry_date" +%s) - $(date +%s)) / 86400))
    if [ $days_until -gt 30 ]; then
        echo -e "  ${GREEN}✓ Certificate Valid: $expiry_date ($days_until days remaining)${NC}"
    elif [ $days_until -gt 0 ]; then
        echo -e "  ${YELLOW}⚠ Certificate Expiring Soon: $days_until days remaining${NC}"
    else
        echo -e "  ${RED}✗ Certificate Expired!${NC}"
    fi
else
    echo -e "  ${YELLOW}⚠ Could not read certificate${NC}"
fi

echo ""
echo -e "${BLUE}=== End of Report ===${NC}"
echo "Generated: $(date)"
