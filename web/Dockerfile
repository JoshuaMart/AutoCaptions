FROM php:8.4-apache

WORKDIR /var/www/html
COPY . .

RUN chown -R www-data:www-data /var/www/html

# Increase PHP upload limits
RUN echo "upload_max_filesize = 500M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 500M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini
