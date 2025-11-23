FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy PHP configuration
COPY php.ini /usr/local/etc/php/

# Set working directory
WORKDIR /var/www/html