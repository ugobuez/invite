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

# Copy source code and entrypoint script
COPY . /var/www/html/
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Set permissions and make entrypoint executable
RUN chown -R www-data:www-data /var/www/html \
    && chmod +x /usr/local/bin/entrypoint.sh

# Use entrypoint to bind Apache to the runtime PORT
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]