FROM serversideup/php:8.1-fpm-nginx

# The demo page lives in demo/, and it boots the library from ../vendor/autoload.php.
ENV NGINX_WEBROOT=/var/www/html/demo

WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist

COPY --chown=www-data:www-data . .
