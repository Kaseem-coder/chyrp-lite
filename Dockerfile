FROM php:8.3-apache

# Copy app files
COPY --chown=www-data . /var/www/html/

# Install only what is absolutely needed
RUN apt-get update && apt-get install -y --no-install-recommends \
      unzip \
      git \
      libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_sqlite

# Make directories writable
RUN mkdir -p /data/ \
    && chown -R www-data:www-data /data /var/www/html

USER www-data

EXPOSE 80

VOLUME /data
VOLUME /var/www/html/uploads

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["apache2-foreground"]
