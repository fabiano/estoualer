FROM php:8.5.2-apache

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy the application source code to the Apache document root
COPY src/ /var/www/html/
