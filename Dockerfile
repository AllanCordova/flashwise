FROM php:8.3.4-fpm

RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-enable pdo_mysql

RUN apt-get update -y && apt-get install -y libzip-dev zip
RUN docker-php-ext-install zip

# Configure PHP upload limits
RUN echo "upload_max_filesize = 25M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Create upload directories with correct permissions
RUN mkdir -p /var/www/public/assets/uploads/materials \
    && mkdir -p /var/www/public/assets/uploads/avatars \
    && chmod -R 777 /var/www/public/assets/uploads