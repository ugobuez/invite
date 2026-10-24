FROM php:8.3-apache

# Copy your PHP application files into the web root
COPY . /var/www/html/
WORKDIR /var/www/html

EXPOSE 10000

# Use PHP's built-in web server (bypasses all Apache permission and config errors)
CMD php -S 0.0.0.0:${PORT:-10000}