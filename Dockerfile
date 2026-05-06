FROM php:8.0-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Enable mod_headers
RUN a2enmod headers

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set up document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/default-ssl.conf

# Copy custom apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Restart Apache
RUN service apache2 restart

WORKDIR /var/www/html
