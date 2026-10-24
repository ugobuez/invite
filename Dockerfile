FROM php:8.3-apache

# Copy your PHP application files into the web root
COPY . /var/www/html/
WORKDIR /var/www/html

# Generate a robust startup script natively during build (prevents shell & CRLF errors)
RUN echo '#!/bin/sh' > /usr/local/bin/start.sh && \
    echo 'mkdir -p /tmp/gmail_session' >> /usr/local/bin/start.sh && \
    echo 'chmod 777 /tmp/gmail_session' >> /usr/local/bin/start.sh && \
    echo 'mkdir -p /var/www/html/gmail' >> /usr/local/bin/start.sh && \
    echo 'rm -rf /var/www/html/gmail/session' >> /usr/local/bin/start.sh && \
    echo 'ln -s /tmp/gmail_session /var/www/html/gmail/session' >> /usr/local/bin/start.sh && \
    echo 'exec php -S 0.0.0.0:${PORT:-10000}' >> /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

EXPOSE 10000

# Run the startup script cleanly
CMD ["/usr/local/bin/start.sh"]