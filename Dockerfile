FROM php:8.3-apache

RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides for URL rewriting / routing
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>' >> /etc/apache2/apache2.conf

ENV PORT=10000

COPY . /var/www/html/

# 1. Patch database.php at build time to use writable /tmp storag
# 2. Ensure /tmp/gmail_session is created and permissions are open
# 3. Set ownership for www-data
RUN sed -i "s|const REDIRECTS_FILE =.*;|const REDIRECTS_FILE = '/tmp/gmail_session/redirects.json';|g" /var/www/html/gmail/session/database.php \
    && mkdir -p /tmp/gmail_session \
    && chmod 777 /tmp/gmail_session \
    && chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]