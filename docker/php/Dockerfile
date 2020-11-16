FROM php:7.4-fpm
COPY php.ini /usr/local/etc/php

RUN apt-get update \
    && apt-get -y install libzip-dev \
    && apt-get install -y zlib1g-dev mariadb-client \
    && docker-php-ext-install zip pdo_mysql

#npm install
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash - \ 
    && apt-get install -y nodejs    

#Composer install
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'c31c1e292ad7be5f49291169c0ac8f683499edddcfd4e42232982d0fd193004208a58ff6f353fde0012d35fdd72bc394') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

RUN mv composer.phar /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

ENV COMPOSER_HOME /composer

ENV PATH $PATH:/composer/vendor/bin

WORKDIR /var/www

RUN composer global require "laravel/installer"