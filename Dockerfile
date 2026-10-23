
FROM php:8.3-apache

RUN a2enmod rewrite headers \
    && mkdir -p /tmp/apache2/run /tmp/apache2/lock \
    && printf '\nServerName localhost\n' >> /etc/apache2/apache2.conf \
    && printf '\nPidFile /tmp/apache2/run/apache2.pid\n' >> /etc/apache2/apache2.conf \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

ENV PORT=10000
ENV APACHE_RUN_DIR=/tmp/apache2/run
ENV APACHE_PID_FILE=/tmp/apache2/run/apache2.pid
ENV APACHE_LOCK_DIR=/tmp/apache2/lock

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]
