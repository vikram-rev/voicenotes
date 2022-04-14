FROM php:7.4-apache
RUN set -eux; \
  apt-get update; \
  apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev zlib1g-dev zip git; \
  pecl install mongodb; \
  docker-php-ext-enable mongodb; \
  cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . /var/www
WORKDIR /var/www
RUN /usr/bin/composer install --prefer-dist --no-scripts --no-dev && rm -rf /root/.composer
RUN set -eux; \
  chmod +x /var/www/.docker/php/entrypoint.sh; \
  chown -R www-data:www-data /var/www; \
  chmod 777 /var/www/public; \
  a2enmod rewrite; \
  sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf
USER www-data
EXPOSE 80