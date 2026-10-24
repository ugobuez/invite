FROM php:8.3-apache

# Enable required modules and set up runtime directories
RUN a2enmod rewrite headers \
    && mkdir -p /tmp/apache2/run /tmp/apache2/lock \
    && chmod 777 /tmp/apache2/run /tmp/apache2/lock \
    && printf '\nServerName localhost\n' >> /etc/apache2/apache2.conf

ENV APACHE_RUN_DIR=/tmp/apache2/run
ENV APACHE_PID_FILE=/tmp/apache2/run/apache2.pid
ENV APACHE_LOCK_DIR=/tmp/apache2/lock

# Copy your PHP application files
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

# Dynamically generate a writable Apache config in /tmp using the runtime PORT, then start Apache
CMD PORT="${PORT:-10000}" && \
    printf "ServerName localhost\n" > /tmp/apache.conf && \
    printf "Mutex file:/tmp/apache2/run default\n" >> /tmp/apache.conf && \
    printf "PidFile /tmp/apache2/run/apache2.pid\n" >> /tmp/apache.conf && \
    printf "Timeout 300\n" >> /tmp/apache.conf && \
    printf "KeepAlive On\n" >> /tmp/apache.conf && \
    printf "User www-data\n" >> /tmp/apache.conf && \
    printf "Group www-data\n" >> /tmp/apache.conf && \
    printf "Include /etc/apache2/mods-enabled/*.load\n" >> /tmp/apache.conf && \
    printf "Include /etc/apache2/mods-enabled/*.conf\n" >> /tmp/apache.conf && \
    printf "Listen %s\n" "$PORT" >> /tmp/apache.conf && \
    printf "<VirtualHost *:%s>\n" "$PORT" >> /tmp/apache.conf && \
    printf "    DocumentRoot /var/www/html\n" >> /tmp/apache.conf && \
    printf "    ErrorLog /dev/stderr\n" >> /tmp/apache.conf && \
    printf "    CustomLog /dev/stdout combined\n" >> /tmp/apache.conf && \
    printf "</VirtualHost>\n" >> /tmp/apache.conf && \
    exec apache2 -f /tmp/apache.conf -D FOREGROUND