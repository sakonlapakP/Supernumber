FROM php:8.2-cli-bookworm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

COPY docker/start-cloud-run-demo.sh /usr/local/bin/start-cloud-run-demo.sh
RUN chmod +x /usr/local/bin/start-cloud-run-demo.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/database.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    FILESYSTEM_DISK=local \
    LINE_CHANNEL_ACCESS_TOKEN= \
    LINE_GROUP_ID=

EXPOSE 8080

CMD ["/usr/local/bin/start-cloud-run-demo.sh"]
