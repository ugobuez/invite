FROM php:8.3-apache

# Copy your PHP application files into the web root
COPY . /var/www/html/
WORKDIR /var/www/html

# Cleanly redirect REDIRECTS_FILE directly to /tmp/gmail_session/redirects.json
RUN sed -i "s|const REDIRECTS_FILE =.*;|const REDIRECTS_FILE = '/tmp/gmail_session/redirects.json';|g" /var/www/html/gmail/session/database.php \
    && mkdir -p /tmp/gmail_session \
    && chmod 777 /tmp/gmail_session

# Generate the startup script
RUN echo '#!/bin/sh' > /usr/local/bin/start.sh && \
    echo 'mkdir -p /tmp/gmail_session' >> /usr/local/bin/start.sh && \
    echo 'chmod 777 /tmp/gmail_session' >> /usr/local/bin/start.sh && \
    echo 'exec php -S 0.0.0.0:${PORT:-10000}' >> /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]