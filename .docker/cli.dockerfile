# CLI container.
#
# All CLI operations performed in this container.
#
# @see https://hub.docker.com/r/uselagoon/php-8.1-cli-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-cli-drupal
FROM uselagoon/php-8.1-cli-drupal:23.7.0

# Add missing variables.
# @todo Remove once https://github.com/uselagoon/lagoon/issues/3121 is resolved.
ARG LAGOON_PR_HEAD_BRANCH=""
ENV LAGOON_PR_HEAD_BRANCH=$LAGOON_PR_HEAD_BRANCH
ARG LAGOON_PR_HEAD_SHA=""
ENV LAGOON_PR_HEAD_SHA=$LAGOON_PR_HEAD_SHA

# Webroot is used for Drush aliases.
ARG WEBROOT=web

ARG GITHUB_TOKEN=""
ENV GITHUB_TOKEN=$GITHUB_TOKEN

# Set default values for environment variables.
# These values will be overridden if set in docker-compose.yml or .env file
# during build stage.
ENV WEBROOT=${WEBROOT} \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/.composer/cache \
    MYSQL_HOST=mariadb \
    SIMPLETEST_DB=mysql://drupal:drupal@mariadb/drupal \
    SIMPLETEST_BASE_URL=http://nginx:8080 \
    SYMFONY_DEPRECATIONS_HELPER=disabled

# Strating from this line, Docker will add result of each command into a
# separate layer. These layers are then cached and re-used when the project is
# rebuilt.
# Note that layers are only rebuilt if files added into the image with `ADD`
# have changed since the last build. So, adding files that are unlikely to
# change earlier in the build process (closer to the start of this file)
# reduce build time.

# Adding more tools.
RUN apk add --no-cache ncurses=6.4_p20230506-r0 pv=1.6.20-r1

# Adding patches and scripts.
COPY patches /app/patches
COPY scripts /app/scripts

# Copy files required for PHP dependencies resolution.
# Note that composer.lock is not explicitly copied, allowing to run the stack
# without existing lock file (this is not advisable, but allows to build
# using latest versions of packages). composer.lock should be comitted to the
# repository.
# File .env (and other environment files) is copied into image as it may be
# required by composer scripts to get some additions variables.
COPY composer.json composer.* .env* auth* /app/

# Install PHP dependencies without including development dependencies. This is
# crucial as it prevents potential security vulnerabilities from being exposed
# to the production environment.
RUN if [ -n "$GITHUB_TOKEN" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"$GITHUB_TOKEN\"}}"; fi && \
    COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader

# Install NodeJS dependencies.
# Note that package-lock.json is not explicitly copied, allowing to run the
# stack without existing lock file (this is not advisable, but allows to build
# using latest versions of packages). package-lock.json should be comitted to
# the repository.
# File Gruntfile.js is copied into image as it is required to generate
# front-end assets.
COPY ${WEBROOT}/themes/custom/your_site_theme/Gruntfile.js ${WEBROOT}/themes/custom/your_site_theme/.eslintrc.json ${WEBROOT}/themes/custom/your_site_theme/package.json ${WEBROOT}/themes/custom/your_site_theme/package* /app/${WEBROOT}/themes/custom/your_site_theme/
COPY ${WEBROOT}/themes/custom/your_site_theme/patches /app/${WEBROOT}/themes/custom/your_site_theme/patches

# Install NodeJS dependencies.
# Since Drupal does not use NodeJS for production, it does not matter if we
# install development dependencnies here - they are not exposed in any way.
RUN npm --prefix /app/${WEBROOT}/themes/custom/your_site_theme install --no-audit --no-progress --unsafe-perm

# Copy all files into appllication source directory. Existing files are always
# overridden.
COPY . /app

# Compile front-end assets. Running this after copying all files as we need
# sources to compile assets.
WORKDIR /app/${WEBROOT}/themes/custom/your_site_theme
RUN npm run build

WORKDIR /app
