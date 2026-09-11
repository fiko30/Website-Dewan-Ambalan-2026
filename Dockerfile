FROM php:8.2

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy project
COPY . /var/www/html

# Expose port (akan di-override oleh Railway)
EXPOSE 80

# Start PHP built-in server dengan PORT dari Railway
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80} -t /var/www/html"]