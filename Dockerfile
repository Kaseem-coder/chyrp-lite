FROM php:8.3-apache

# Install required system libraries
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev libonig-dev libzip-dev unzip git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd mbstring pdo pdo_sqlite pdo_mysql pdo_pgsql zip

# Enable Apache rewrite (needed for pretty URLs)
RUN a2enmod rewrite

# Copy code into container
COPY . /var/www/html

# Ensure uploads/cache are writable
RUN mkdir -p /var/www/html/chyrp-data \
    && chown -R www-data:www-data /var/www/html /var/www/html/uploads /var/www/html/cache /var/www/html/chyrp-data

# PHP config tweaks (for uploads)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=100M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size=100M" >> "$PHP_INI_DIR/php.ini"

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
