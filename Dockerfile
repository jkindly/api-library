# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.4 AS app

ENV APP_ENV=prod

WORKDIR /app

# PHP extensions required by the application.
RUN install-php-extensions \
        pdo_pgsql \
        intl \
        opcache \
        zip

# Bring in Composer from its official image.
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Install PHP dependencies first to leverage Docker layer caching.
# composer.lock is committed, so the install is fully reproducible.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-progress --no-interaction --prefer-dist

# Serve plain HTTP on :80 (no auto-HTTPS redirect inside the container).
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# Copy the rest of the application source.
COPY . .

# Optimise the autoloader and warm up the production cache (no DB access needed).
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
 && php bin/console cache:clear --no-debug \
 && php bin/console cache:warmup --no-debug

# Entry point waits for the database, runs migrations, then starts FrankenPHP.
COPY docker/docker-entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
