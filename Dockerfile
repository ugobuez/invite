FROM php:8.3-apache

# Enable required modules during the build phase
RUN a2enmod rewrite headers \
    && mkdir -p /tmp/apache2/run /tmp/apache2/lock \
    && chmod 777 /tmp/apache2/run /tmp/apache2/lock

# Copy your PHP application files
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

# At runtime, generate a clean writable Apache config in /tmp using the dynamic PORT
CMD PORT="${PORT:-10000}" && \
    printf "ServerName localhost\n" > /tmp/apache.conf && \
    printf "PidFile /tmp/apache2.pid\n" >> /tmp/apache.conf && \
    printf "User root\n" >> /tmp/apache.conf && \
    printf "Group root\n" >> /tmp/apache.conf && \
    printf "Listen %s\n" "$PORT" >> /tmp/apache.conf && \
    printf "DocumentRoot /var/www/html\n" >> /tmp/apache.conf && \
    printf "<Directory /var/www/html>\n" >> /tmp/apache.conf && \
    printf "    AllowOverride All\n" >> /tmp/apache.conf && \
    printf "    Require all granted\n" >> /tmp/apache.conf && \
    printf "</Directory>\n" >> /tmp/apache.conf && \
    printf "IncludeOptional /etc/apache2/mods-enabled/*.load\n" >> /tmp/apache.conf && \
    printf "IncludeOptional /etc/apache2/mods-enabled/*.conf\n" >> /tmp/apache.conf && \
    exec apache2 -f /tmp/apache.conf -D FOREGROUND