FROM php:8.3-apache

# Copy your PHP application files into the web root
COPY . /var/www/html/
WORKDIR /var/www/html

# Permanently patch database.php during build to use the writable /tmp folder
RUN sed -i "s|const REDIRECTS_FILE =.*;|const REDIRECTS_FILE = '/tmp/gmail_session/redirects.json';|g" /var/www/html/gmail/session/database.php \
    && mkdir -p /tmp/gmail_session \
    && chmod 777 /tmp/gmail_session

# Generate a startup script that correctly binds to Hostless's dynamic PORT
RUN echo '#!/bin/sh' > /usr/local/bin/start.sh && \
    echo 'mkdir -p /tmp/gmail_session' >> /usr/local/bin/start.sh && \
    echo 'chmod 777 /tmp/gmail_session' >> /usr/local/bin/start.sh && \
    echo 'exec php -S 0.0.0.0:${PORT:-10000}' >> /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

EXPOSE 10000

# Run the startup script
CMD ["/usr/local/bin/start.sh"]