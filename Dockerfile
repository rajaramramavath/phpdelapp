FROM php:8.0-apache

# Install necessary packages and PHP extensions
RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
    && docker-php-ext-install dom curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy the current directory contents into the container
COPY . .

# Expose port 80
EXPOSE 80
