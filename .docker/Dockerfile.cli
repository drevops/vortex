# @see https://github.com/amazeeio/lagoon/tree/master/images/php/cli-drupal
FROM amazeeio/php:7.2-cli-drupal-v0.22.1

ENV WEBROOT=docroot \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/.composer/cache \
    MYSQL_HOST=mariadb

RUN apk update \
    && apk add pv \
    && rm -rf /var/cache/apk/*

ENV DOCKERIZE_VERSION v0.6.1
RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

ADD patches /app/patches
ADD scripts /app/scripts

COPY .env composer.json composer.* /app/

RUN echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory.ini \
    && composer install -n --no-dev --ansi --prefer-dist --no-suggest --optimize-autoloader \
    && rm -rf /usr/local/etc/php/conf.d/memory.ini

COPY package.json package-lock.json Gruntfile.js /app/
RUN npm install

COPY . /app

RUN npm run build
