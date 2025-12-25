FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libsqlite3-dev \
    default-mysql-client \
    libmariadb-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Note: Permissions for Laravel directories are set at runtime via setup.sh
# since /var/www/html is mounted as a volume and doesn't exist during build

WORKDIR /var/www/html

