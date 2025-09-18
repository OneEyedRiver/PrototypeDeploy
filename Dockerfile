FROM php:8.2

# System deps
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    zip \
    sqlite3 \
    libsqlite3-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    curl \
    nodejs \
    npm \
&& docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install pdo pdo_sqlite zip gd


# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set workdir
WORKDIR /var/www

# Copy project files
COPY . .

# Laravel dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Create SQLite DB
RUN mkdir -p /var/www/database && touch /var/www/database/database.sqlite

# Env & key (still safe to keep)
RUN cp .env.example .env && php artisan key:generate

# Expose port
EXPOSE 8080

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install Node deps and build Tailwind
RUN npm install && npm run build

# Start server with migrations + storage link
CMD php artisan migrate --force && php artisan storage:link && php -S 0.0.0.0:8080 -t public
