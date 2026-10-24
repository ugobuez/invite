FROM php:8.3-apache

# Copy your PHP application files into the web root
COPY . /var/www/html/
WORKDIR /var/www/html

EXPOSE 10000

# Redirect the session folder to a writable /tmp directory, then start PH
CMD mkdir -p /tmp/gmail_session && \
    mkdir -p /var/www/html/gmail && \
    rm -rf /var/www/html/gmail/session && \
    ln -s /tmp/gmail_session /var/www/html/gmail/session && \
    php -S 0.0.0.0:${PORT:-10000}