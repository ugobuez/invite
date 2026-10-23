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

# Generate the entrypoint script using absolute path and safe shell syntax
RUN printf '#!/bin/sh\n' > /usr/local/bin/entrypoint.sh && \
    printf 'PORT="${PORT:-80}"\n' >> /usr/local/bin/entrypoint.sh && \
    printf 'sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf\n' >> /usr/local/bin/entrypoint.sh && \
    printf 'sed -i "s/:[0-9]*>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf\n' >> /usr/local/bin/entrypoint.sh && \
    printf 'exec "$@"\n' >> /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

# Copy source code and set permissions
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

# Use the absolute path for the entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]