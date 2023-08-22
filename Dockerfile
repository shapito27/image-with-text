FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y git

## Install PHP extensions
RUN apt-get update && apt-get install -y \
		libfreetype-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
        git \
        zip \
        vim \
        unzip \
        libonig-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) mbstring

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

## Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

COPY --chown=www:www . /var/www/

## Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD bash -c "composer install && php-fpm"
