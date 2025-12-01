FROM php:8.2-fpm

RUN apt-get update \
    && apt-get install -y \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libonig-dev \
        libmagickwand-dev \
        zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        zip \
        pdo_mysql \
        mbstring \
        exif \
        opcache \
    && pecl install \
        redis \
        igbinary \
        msgpack \
        imagick \
    && docker-php-ext-enable \
        redis \
        igbinary \
        msgpack \
        imagick \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY .docker/php-fpm/php.ini-development /usr/local/etc/php/php.ini
COPY .docker/php-fpm/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY .docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY .docker/php-fpm/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN curl -sS https://getcomposer.org/installer | php -- \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
