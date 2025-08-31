FROM php:8.3-apache

# Copy app files
COPY --chown=www-data . /var/www/html/

# Install dependencies (without GD for now)
RUN apt-get update && apt-get install -y --no-install-recommends \
      libonig-dev \
      libpq-dev \
      libsqlite3-dev \
      unzip \
      git \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) \
      mbstring \
      pdo \
      pdo_sqlite \
      pdo_mysql \
      pdo_pgsql \
      zip

# Create writable dirs
RUN mkdir -p /data/ \
    && chown -R www-data:www-data /data /var/www/html

USER www-data

EXPOSE 80

VOLUME /data
VOLUME /var/www/html/uploads

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["apache2-foreground"]
