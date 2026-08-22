# Use the official PHP CLI image because this app is served with
# `php artisan serve` instead of Apache or Nginx inside the container.
FROM php:8.4-cli

# All app files and commands run from Laravel's project root.
WORKDIR /var/www/html

# Install system packages needed by Composer and PHP extensions.
# `libpq-dev` is required to compile the Postgres PDO driver.
# `libzip-dev` is required for PHP's zip extension.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from the official Composer image instead of installing it manually.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies before copying the whole app so Docker can cache
# this layer when only application code changes.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy the Laravel application source into the image.
COPY . .

# Create a container-local env file from the safe example file. Docker Compose
# still overrides these values at runtime, and real local secrets stay ignored.
RUN cp .env.example .env \
    && sed -i 's|^APP_KEY=$|APP_KEY=base64:DvpfzRoNKQVPRVJDnCpembliSha2Vu2fsFAKGH+Hnlk=|' .env \
    && sed -i 's|^APP_URL=.*|APP_URL=http://localhost:7000|' .env \
    && sed -i 's|^DB_HOST=.*|DB_HOST=database|' .env \
    && sed -i 's|^DB_PORT=.*|DB_PORT=5432|' .env \
    && sed -i 's|^DB_DATABASE=.*|DB_DATABASE=bumpa|' .env \
    && sed -i 's|^DB_USERNAME=.*|DB_USERNAME=bumpa|' .env \
    && sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=secret|' .env \
    && sed -i 's|^CASHBACK_PAYMENT_PROVIDER=.*|CASHBACK_PAYMENT_PROVIDER=payment_mock|' .env \
    && sed -i 's|^PAYMENT_MOCK_MODE=.*|PAYMENT_MOCK_MODE=docker|' .env \
    && composer dump-autoload --optimize

# The Laravel development server listens on port 8000 inside the container.
EXPOSE 8000

# Default command for running this image directly with `docker run`.
# Docker Compose overrides this with its own command so it can run migrations first.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
