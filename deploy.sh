#!/bin/bash

# =========================
# Laravel deployment script for IONOS staging
# =========================

# SSH info
SSH_USER="u117366619"
SSH_HOST="access1016119319.webspace-data.io"
REMOTE_DIR="/kunden/homepages/46/d1016119319/htdocs/staging"

# GitHub repo
GIT_REPO="git@github.com:bashyauri/future-academy.git"

# Local vendor zip name (already uploaded to server)
VENDOR_ZIP="vendor.zip"

# Connect to server
ssh $SSH_USER@$SSH_HOST << 'ENDSSH'

echo "============================"
echo "Starting deployment..."
echo "============================"

cd /kunden/homepages/46/d1016119319/htdocs/staging

# 1. Clone or update repository
if [ -d ".git" ]; then
    echo "Pulling latest changes from GitHub..."
    git reset --hard
    git pull origin main
else
    echo "Cloning repository..."
    git clone git@github.com:bashyauri/future-academy.git .
fi

# 2. Extract vendor if missing
if [ ! -d "vendor" ]; then
    echo "Vendor folder missing, extracting vendor.zip..."
    if [ -f "$VENDOR_ZIP" ]; then
        unzip -o $VENDOR_ZIP -d .
        echo "Vendor installed successfully."
    else
        echo "ERROR: vendor.zip not found!"
        exit 1
    fi
fi

# 3. Generate APP_KEY if missing
if ! grep -q "APP_KEY=" .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate
fi

# 4. Clear and cache config, routes, views
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 5. Run migrations (force for staging)
echo "Running migrations..."
php artisan migrate --force

# 6. Set folder permissions
echo "Setting folder permissions..."
chmod -R 755 storage bootstrap/cache

echo "============================"
echo "Deployment finished successfully!"
echo "============================"

ENDSSH
