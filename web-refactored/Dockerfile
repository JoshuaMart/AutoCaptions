# Use the official PHP 8.4 image with Apache
FROM php:8.4-apache

# Set the working directory
WORKDIR /var/www/html

# Install system dependencies and common PHP extensions
# - libzip-dev, libpng-dev, etc. for gd and zip extensions
# - a2enmod rewrite to enable Apache's URL rewriting
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql \
    && a2enmod rewrite

# Copy the custom Apache configuration to point to the /public folder
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copy the application files
COPY . .

# Increase PHP upload limits
RUN echo "upload_max_filesize = 500M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 500M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini

# Set permissions.
# The "storage" and "public/assets" folders need to be writable by Apache.
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/public/assets \
    && chmod -R 775 /var/www/html/storage \
    /var/www/html/public/assets
