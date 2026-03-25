FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Fix permissions & prepare directories
RUN chown -R www-data:www-data /var/www \
    && mkdir -p config/jwt public/uploads/events var/cache var/log \
    && chmod -R 755 /var/www/var

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-scripts

EXPOSE 9000

CMD ["php-fpm"]
