FROM php:8.3-fpm

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev

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

# Security-focused PHP configuration
RUN set -eux; \
    printf "\n; Security hardening\n"               > /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "expose_php=0\n"                        >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "display_errors=0\n"                    >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "log_errors=1\n"                         >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "cgi.fix_pathinfo=0\n"                   >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "session.cookie_httponly=1\n"            >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "session.use_strict_mode=1\n"            >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "zend.assertions=-1\n"                   >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "enable_dl=0\n"                           >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "post_max_size=16M\n"                    >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    printf "upload_max_filesize=16M\n"              >> /usr/local/etc/php/conf.d/zzz-security.ini; \
    # Restrict PHP-FPM to only handle .php
    sed -ri 's~^;?security\.limit_extensions = .*$~security.limit_extensions = .php~' /usr/local/etc/php-fpm.d/www.conf

# Create uploads dir and set safe ownership/permissions
# RUN mkdir -p /var/www/html/public/uploads \
#     && chown -R www-data:www-data /var/www/html/public/uploads \
#     && chmod 02770 /var/www/html/public/uploads

COPY conf.d/20-security.ini /usr/local/etc/php/conf.d/20-security.ini

# Drop root
USER www-data