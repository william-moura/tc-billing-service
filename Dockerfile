FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    linux-headers \
    build-base \
    autoconf \
    curl-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    zip \
    unzip \
    git \
    supervisor \
    rabbitmq-c-dev

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    sockets \
    bcmath \
    pcntl \
    mbstring \
    intl

RUN pecl install mongodb amqp \
    && docker-php-ext-enable mongodb amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 1. Copia apenas os arquivos de dependência primeiro (cache eficiente)
COPY composer.json composer.lock ./

# 2. Instala pacotes sem disparar artisan package:discover
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

# 3. Copia o restante do código e gera o autoload otimizado
COPY . .
RUN composer dump-autoload --optimize --no-scripts

RUN git config --global --add safe.directory /var/www/html \
    && composer install --no-interaction --prefer-dist --optimize-autoloader

RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]