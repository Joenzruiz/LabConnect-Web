FROM php:8.2-apache

# Install PostgreSQL client libraries and PDO pgsql extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite and headers modules
RUN a2enmod rewrite headers

# Copy application code
COPY . /var/www/html/

# Ensure uploads directory exists and is writable
RUN mkdir -p /var/www/html/uploads/results && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 775 /var/www/html/uploads

EXPOSE 80
