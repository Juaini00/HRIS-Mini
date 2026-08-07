FROM php:8.4-cli-alpine
RUN apk add --no-cache git curl libpq-dev icu-dev oniguruma-dev nodejs npm \
    && docker-php-ext-install pdo_pgsql intl mbstring
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && npm ci && npm run build
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
