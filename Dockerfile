# Build stage
FROM ghcr.io/railwayapp/nixpacks:ubuntu-22.04 as builder

# Install system deps
RUN apt-get update && apt-get install -y curl git unzip && rm -rf /var/lib/apt/lists/*

# Install PHP 8.3 + composer via deb.sury
RUN apt-get update && \
    apt-get install -y software-properties-common && \
    add-apt-repository ppa:ondrej/php -y && \
    apt-get update && \
    apt-get install -y \
      php8.3 php8.3-cli php8.3-common php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-gd \
      php8.3-sqlite3 php8.3-zip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Copy app files
COPY composer.json composer.lock /app/
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY package.json package-lock.json /app/
RUN npm ci && npm run build

COPY . /app

# Runtime stage
FROM ubuntu:22.04

RUN apt-get update && apt-get install -y \
    php8.3 php8.3-cli php8.3-common php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-gd \
    php8.3-sqlite3 php8.3-zip && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy built app from builder
COPY --from=builder /app /app

ENV PORT=8080
EXPOSE 8080

# Ensure storage/cache permissions
RUN mkdir -p storage bootstrap/cache && chmod -R 775 storage bootstrap/cache

# Start script
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

CMD ["/usr/local/bin/docker-start.sh"]
