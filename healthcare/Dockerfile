FROM php:8.2-apache

# Install PostgreSQL client libraries and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mysqli \
    && docker-php-ext-enable pdo_pgsql pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set Apache working directory
WORKDIR /var/www/html

# Copy all healthcare web files into Apache document root
COPY . /var/www/html/

# Set correct file ownership and permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache to use Render's dynamic PORT environment variable (default: 80)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Expose port (Render automatically listens on this port)
ENV PORT=80
EXPOSE 80

CMD ["apache2-foreground"]
