FROM php:8.3-apache

# Enable required modules and create writable runtime directories
RUN a2enmod rewrite headers \
    && mkdir -p /tmp/apache2/run /tmp/apache2/lock \
    && chmod 777 /tmp/apache2/run /tmp/apache2/lock \
    && sed -ri \
        -e 's!^export APACHE_RUN_DIR=.*!export APACHE_RUN_DIR=/tmp/apache2/run!' \
        -e 's!^export APACHE_PID_FILE=.*!export APACHE_PID_FILE=/tmp/apache2/run/apache2.pid!' \
        -e 's!^export APACHE_LOCK_DIR=.*!export APACHE_LOCK_DIR=/tmp/apache2/lock!' \
        /etc/apache2/envvars \
    && printf '\nServerName localhost\n' >> /etc/apache2/apache2.conf

ENV APACHE_RUN_DIR=/tmp/apache2/run
ENV APACHE_PID_FILE=/tmp/apache2/run/apache2.pid
ENV APACHE_LOCK_DIR=/tmp/apache2/lock

# Copy source code
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

# Dynamically update Apache to listen on Hostless's assigned runtime PORT at boot
CMD sed -i "s/Listen [0-9]*/Listen ${PORT:-80}/g" /etc/apache2/ports.conf \
    && sed -i "s/:[0-9]*>/:${PORT:-80}>/g" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground