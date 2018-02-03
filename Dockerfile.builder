FROM amazeeio/php:7.1-cli-drupal

ENV WEBROOT=docroot \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/.composer/cache \
    MYSQL_HOST=mariadb

RUN apk update \
    && apk del nodejs nodejs-current yarn \
    && apk add nodejs-npm patch rsync --update-cache --repository http://dl-3.alpinelinux.org/alpine/v3.7/main/ \
    && rm -rf /var/cache/apk/*

ADD patches /app/patches
ADD scripts /app/scripts

COPY composer.json composer.lock /app/
COPY package.json package-lock.json Gruntfile.js /app/

RUN composer install --no-dev --optimize-autoloader --prefer-dist --ansi

RUN npm install

COPY . /app
COPY .data/db.sql /tmp/.data/db.sql

RUN npm run build
RUN rm -rf /app/node_modules
