FROM php:8.3-apache

RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

ENV PORT=10000

COPY . /var/www/html/

# Ensure Apache (www-data) owns the files so it can write to JSON/session files
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]