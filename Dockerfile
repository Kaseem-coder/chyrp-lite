FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libwebp-dev \
      zlib1g-dev \
      libonig-dev \
      libpq-dev \
      unzip \
      git \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd mbstring pdo pdo_sqlite pdo_mysql pdo_pgsql zip

# Copy project files into container
COPY --chown=www-data:www-data . /var/www/html/

# PHP config tweaks
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/post_max_size = .*/post_max_size = 100M/' "$PHP_INI_DIR/php.ini"

# Create data + uploads directory
RUN mkdir -p /var/www/html/chyrp-data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/chyrp-data /var/www/html/uploads

USER www-data

EXPOSE 80

VOLUME /var/www/html/chyrp-data
VOLUME /var/www/html/uploads

CMD ["apache2-foreground"]

