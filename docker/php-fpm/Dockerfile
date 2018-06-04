FROM php:7.0-fpm-alpine

#
# Install dependencies
#
RUN apk update && apk add --no-cache --virtual .build-deps autoconf file pkgconf libc-dev gcc make g++ zlib-dev libmemcached-dev re2c cyrus-sasl-dev \
    && apk add --no-cache libmemcached-libs zlib \
    #
    # Install xdebug
    && pecl install xdebug-2.5.0 \
    && cd /tmp \
    #
    # Install memcached, pdo and pdo_mysql extensions.
    && wget https://github.com/php-memcached-dev/php-memcached/archive/php7.zip \
    && unzip php7.zip \
    && docker-php-ext-configure /tmp/php-memcached-php7 --disable-memcached-sasl \
    && docker-php-ext-install /tmp/php-memcached-php7 pdo pdo_mysql pcntl sysvmsg sysvsem \
    && docker-php-ext-enable xdebug memcached \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/* \
    && apk del .build-deps && rm -rf tmp/* \
    #
    # Install composer
    && cd /usr/bin \
    && curl --silent --show-error http://getcomposer.org/installer | php \
    && ln -s /usr/bin/composer.phar /usr/bin/composer \
    && chmod +x composer

#
# Configure container
#
RUN echo "xdebug.remote_enable=on" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.remote_autostart=on" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.max_nesting_level=1000" >> /usr/local/etc/php/php.ini \
    && echo "xdebug.remote_port=9001" >> /usr/local/etc/php/php.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/php.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/php.ini \
    #
    # Change user id for fpm user in order to allow change files.
    && sed -i -- 's/82/1000/g' /etc/passwd \
    #
    # Add useful aliases
    && echo "alias ls='ls --color=always'" >> /etc/profile \
    && echo "alias ll='ls -laF'" >> /etc/profile \
    && echo "alias sf='./bin/console'" >> /etc/profile \
    && echo "alias dev='sf --env=dev'" >> /etc/profile \
    && echo "alias prod='sf --env=prod'" >> /etc/profile \
    && echo "alias c='composer'" >> /etc/profile \
    && mkdir /code

EXPOSE 9000

WORKDIR /code