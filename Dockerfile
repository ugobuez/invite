FROM php:8.3-apache

# Enable required modules during build
RUN a2enmod rewrite headers

# Copy your PHP application files
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

# At runtime: copy config to writable /tmp, update port dynamically, and start Apache
CMD rm -rf /tmp/apache2 && cp -r /etc/apache2 /tmp/apache2 && \
    PORT="${PORT:-10000}" && \
    sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /tmp/apache2/ports.conf && \
    sed -i "s/:[0-9]*>/:${PORT}>/g" /tmp/apache2/sites-enabled/000-default.conf && \
    mkdir -p /tmp/apache2/run /tmp/apache2/lock && \
    chmod 777 /tmp/apache2/run /tmp/apache2/lock && \
    export APACHE_RUN_DIR=/tmp/apache2/run && \
    export APACHE_PID_FILE=/tmp/apache2/run/apache2.pid && \
    export APACHE_LOCK_DIR=/tmp/apache2/lock && \
    exec apache2 -d /tmp/apache2 -f apache2.conf -D FOREGROUND