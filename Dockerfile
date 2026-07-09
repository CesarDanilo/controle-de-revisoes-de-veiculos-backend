FROM php:8.3-cli

# Dependências de sistema necessárias pras extensões PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

EXPOSE 8000

CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan serve --host 0.0.0.0 --port $PORT