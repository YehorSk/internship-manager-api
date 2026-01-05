FROM php:8.2-fpm

ARG UID
ARG GID

RUN if ! getent group ${GID} > /dev/null 2>&1; then \
        groupadd -g ${GID} admin; \
    fi && \
    if ! getent passwd ${UID} > /dev/null 2>&1; then \
        useradd -u ${UID} -g ${GID} -m admin; \
    fi

RUN apt-get update \
    && apt-get install -y gosu libjpeg-dev libpng-dev libfreetype6-dev libzip-dev libicu-dev libxml2-dev libonig-dev libmagickwand-dev zlib1g-dev supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl zip pdo_mysql mbstring exif opcache \
    && pecl install redis igbinary msgpack imagick \
    && docker-php-ext-enable redis igbinary msgpack imagick \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY .docker/php-fpm/php.ini-development /usr/local/etc/php/php.ini
COPY .docker/php-fpm/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY .docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY .docker/php-fpm/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY .docker/php-fpm/first_init.sh /usr/local/bin/first_init.sh

RUN curl -sS https://getcomposer.org/installer | php -- \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/first_init.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
