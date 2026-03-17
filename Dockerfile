FROM node:20-bookworm-slim AS node-builder

WORKDIR /app

COPY package*.json ./
RUN npm install && npm install puppeteer --no-save

COPY . .
RUN npm run build

FROM php:8.2-apache-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PUPPETEER_SKIP_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

RUN apt-get update && apt-get install -y \
    chromium \
    curl \
    default-mysql-client \
    git \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libsqlite3-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl mysqli pcntl pdo_mysql zip \
 && a2enmod headers rewrite \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node-builder /usr/local/bin/node /usr/local/bin/node

WORKDIR /var/www/html

COPY . .
COPY --from=node-builder /app/node_modules /var/www/html/node_modules
COPY --from=node-builder /app/public/build /var/www/html/public/build
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/chromium.sh /usr/local/bin/chromium-no-sandbox
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint

RUN cp -n .env.example .env \
 && composer install --prefer-dist --no-interaction --no-progress \
 && chmod +x /usr/local/bin/chromium-no-sandbox \
 && chmod +x /usr/local/bin/app-entrypoint \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
