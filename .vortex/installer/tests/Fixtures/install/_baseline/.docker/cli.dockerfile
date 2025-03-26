# CLI container.
#
# All CLI operations performed in this container.
#
# hadolint global ignore=DL3018
#
# @see https://hub.docker.com/r/uselagoon/php-8.3-cli-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-cli-drupal

FROM uselagoon/php-8.3-cli-drupal:__VERSION__

# Add missing variables.
# @todo Remove once https://github.com/uselagoon/lagoon/issues/3121 is resolved.
ARG LAGOON_PR_HEAD_BRANCH=""
ENV LAGOON_PR_HEAD_BRANCH=${LAGOON_PR_HEAD_BRANCH}
ARG LAGOON_PR_HEAD_SHA=""
ENV LAGOON_PR_HEAD_SHA=${LAGOON_PR_HEAD_SHA}

ARG WEBROOT=web
ENV WEBROOT=${WEBROOT}

ARG GITHUB_TOKEN=""
ENV GITHUB_TOKEN=${GITHUB_TOKEN}

ARG DRUPAL_THEME="star_wars"
ENV DRUPAL_THEME=${DRUPAL_THEME}

ARG DRUPAL_PUBLIC_FILES="/app/${WEBROOT}/sites/default/files"
ENV DRUPAL_PUBLIC_FILES=${DRUPAL_PUBLIC_FILES}

ARG DRUPAL_PRIVATE_FILES="/app/${WEBROOT}/sites/default/files/private"
ENV DRUPAL_PRIVATE_FILES=${DRUPAL_PRIVATE_FILES}

ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}

ARG DRUPAL_CONFIG_PATH="/app/config/default"
ENV DRUPAL_CONFIG_PATH=${DRUPAL_CONFIG_PATH}

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/.composer/cache \
    SIMPLETEST_DB=mysql://drupal:drupal@database/drupal \
    SIMPLETEST_BASE_URL=http://nginx:8080 \
    SYMFONY_DEPRECATIONS_HELPER=disabled

# Starting from this line, Docker adds the result of each command as a
# separate layer. These layers are cached and reused when the project is
# rebuilt. Layers are only rebuilt if the files added with `ADD` have changed
# since the last build. To reduce build time, add files that rarely change
# earlier in the build process (near the top of this file).

# Add more tools.
RUN apk add --no-cache ncurses pv tzdata autoconf g++ make \
  && pecl install pcov \
  && docker-php-ext-enable pcov \
  && apk del g++ make autoconf

# Add patches and scripts.
COPY patches /app/patches
COPY scripts /app/scripts

# Copy files required for resolving PHP dependencies.
# Note that composer.lock is not copied explicitly, allowing the stack to run
# without an existing lock file. This is not recommended but enables builds
# using the latest package versions. The composer.lock file should be committed
# to the repository.
# The .env file (and other environment files) is copied into the image, as it
# may be needed by Composer scripts to access additional variables.
COPY composer.json composer.* .env* auth* /app/

# Install PHP dependencies without development packages.
# This is crucial to avoid exposing potential security vulnerabilities
# in the production environment.
RUN if [ -n "${GITHUB_TOKEN}" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN}\"}}"; fi && \
    COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader

# Install NodeJS dependencies.
# Note that package-lock.json is not explicitly copied, allowing the stack to
# run without an existing lock file. This is not recommended but enables builds
# using the latest package versions. The package-lock.json file should be
# committed to the repository.
# Gruntfile.js is copied into the image as it is required to generate front-end
# assets.
COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/Gruntfile.js ${WEBROOT}/themes/custom/${DRUPAL_THEME}/.eslintrc.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package* /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/
COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches

# Install NodeJS dependencies.
# Since Drupal does not use NodeJS in production, installing development
# dependencies here is fine — they are not exposed in any way.
RUN npm --prefix /app/${WEBROOT}/themes/custom/${DRUPAL_THEME} ci --no-audit --no-progress --unsafe-perm

# Copy all files into the application source directory. Existing files are
# always overwritten.
COPY . /app

# Create file directories and set correct permissions.
RUN mkdir -p "${DRUPAL_PUBLIC_FILES}" "${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" "${DRUPAL_CONFIG_PATH}" && \
 chmod 0770 "${DRUPAL_PUBLIC_FILES}" "${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" "${DRUPAL_CONFIG_PATH}"

# Compile front-end assets. This runs after copying all files, as source files
# are needed for compilation.
WORKDIR /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}
RUN npm run build

WORKDIR /app
