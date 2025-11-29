# PHP-FPM untuk Nginx
FROM php:8.2-fpm
# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql
# Create session directory
RUN mkdir -p /var/lib/php/sessions && chown -R www-data:www-data /var/lib/php/sessions
# Copy custom php.ini
COPY php.ini /usr/local/etc/php/php.ini
# Copy custom PHP-FPM configuration
COPY www.conf /usr/local/etc/php-fpm.d/www.conf
# Set working directory
WORKDIR /var/www/myphpapp
# Set proper permissions
RUN chown -R www-data:www-data /var/www/myphpapp
# Switch to www-data user
USER www-data
# Expose port 9000 untuk PHP-FPM
EXPOSE 9000
