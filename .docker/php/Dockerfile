FROM php:7.4-apache
RUN apt-get update \
  && apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev zlib1g-dev zip git \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . /var/www/
WORKDIR /var/www
RUN composer install
RUN chown -R www-data:www-data /var/www && a2enmod rewrite
EXPOSE 80