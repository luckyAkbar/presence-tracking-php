FROM php:8.3-fpm

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    git \
    unzip

# Configure and install extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    gd \
    pdo_mysql \
    mysqli \
    zip \
    bcmath \
    mbstring \
    exif \
    opcache \
    xml

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer (from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Security-focused PHP-FPM setting: restrict to only handle .php
RUN set -eux; \
    sed -ri 's~^;?security\.limit_extensions = .*$~security.limit_extensions = .php~' /usr/local/etc/php-fpm.d/www.conf

# Create uploads dir and set safe ownership/permissions
# RUN mkdir -p /var/www/html/public/uploads \
#     && chown -R www-data:www-data /var/www/html/public/uploads \
#     && chmod 02770 /var/www/html/public/uploads

COPY conf.d/20-security.ini /usr/local/etc/php/conf.d/20-security.ini

# Expose FPM on 9000 as usual
EXPOSE 9000

# Keep default FPM user (www-data) configured in the base image