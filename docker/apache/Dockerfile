FROM php:7.4-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip libxslt-dev libzip-dev unzip libpq-dev apt-utils tzdata

RUN a2enmod rewrite
RUN docker-php-ext-install xsl
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo_pgsql
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

RUN php composer-setup.php

RUN php -r "unlink('composer-setup.php');"

RUN mv composer.phar /usr/local/bin/composer

ENV TZ=Europe/Paris
RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN userdel -f www-data
RUN if getent group www-data ; then groupdel www-data; fi
RUN groupadd -g ${GROUP_ID} www-data
RUN useradd -l -u ${USER_ID} -g www-data www-data
RUN install -d -m 0755 -o www-data -g www-data /home/www-data
RUN chown --changes --silent --no-dereference --recursive \
          --from=33:33 ${USER_ID}:${GROUP_ID} \
        /home/www-data \
        /var/www

CMD ["apache2-foreground"]