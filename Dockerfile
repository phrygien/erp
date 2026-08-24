########################################
# Stage 1 : build des assets front (Vite)
########################################
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

########################################
# Stage 2 : installation des dépendances PHP (composer)
########################################
FROM composer:2 AS vendor-builder

WORKDIR /app

COPY composer.json composer.lock ./

# On installe sans exécuter les scripts artisan (l'app complète n'est pas encore copiée)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

########################################
# Stage 3 : image finale PHP-FPM
########################################
FROM php:8.3-fpm-alpine AS app

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV} \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dépendances système + librairies nécessaires aux extensions PHP
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        postgresql-dev \
        oniguruma-dev \
        librdkafka-dev \
        supervisor \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        pcntl \
        bcmath \
        intl \
        zip \
        gd \
        mbstring \
        exif \
        opcache \
    # extensions via PECL : Kafka (rdkafka) + Redis
    && pecl install rdkafka redis \
    && docker-php-ext-enable rdkafka redis \
    && apk del $PHPIZE_DEPS

# Composer binaire
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Config PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Code applicatif
COPY . .

# vendor/ pré-installé (stage 2) + assets buildés (stage 1)
COPY --from=vendor-builder /app/vendor ./vendor
COPY --from=node-builder /app/public/build ./public/build

# Finaliser l'autoload / scripts Laravel maintenant que l'app est complète
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi || true

# Permissions (storage / bootstrap cache doivent être writables par www-data)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

########################################
# Stage 4 : image Nginx (sert public/ buildé par le stage app)
########################################
FROM nginx:1.27-alpine AS nginx-runtime

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

EXPOSE 80
