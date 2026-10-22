FROM php:8.3-apache

# Enable Apache rewrite module and configure ports
RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

# Set the environment variable for the port
ENV PORT=10000

# Copy the application code to the container
COPY . /var/www/html/

# Set the correct permissions for the directory where redirects.json will be created
RUN mkdir -p /var/www/html/data && chmod 755 /var/www/html/data

# Expose the port
EXPOSE 10000

# Start Apache in the foreground
CMD ["apache2-foreground"]