FROM php:8.3-apache

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
      libonig-dev \
      libpq-dev \
      libsqlite3-dev \
      unzip \
      git \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) \
      mbstring \
      pdo_sqlite \
      pdo_mysql \
      pdo_pgsql \
      zip

# Copy app
COPY --chown=www-data:www-data . /var/www/html/

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html /var/www/html/includes /var/www/html/uploads

# Apache tweaks
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

USER www-data

EXPOSE 80
