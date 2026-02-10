# CLI container.
#
# All CLI operations performed in this container.
#
# hadolint global ignore=DL3018,SC2174
#
# @see https://hub.docker.com/r/uselagoon/php-8.3-cli-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-cli-drupal

FROM uselagoon/php-8.3-cli-drupal:26.1.0

# Add missing variables.
# @todo Remove once https://github.com/uselagoon/lagoon/issues/3121 is resolved.
ARG LAGOON_PR_HEAD_BRANCH=""
ENV LAGOON_PR_HEAD_BRANCH=${LAGOON_PR_HEAD_BRANCH}
ARG LAGOON_PR_HEAD_SHA=""
ENV LAGOON_PR_HEAD_SHA=${LAGOON_PR_HEAD_SHA}

ARG WEBROOT=web
ENV WEBROOT=${WEBROOT}

# Token is used to access private repositories. Not exposed as an environment
# variable within an image to avoid baking it into the image.
ARG PACKAGE_TOKEN=""

ARG DRUPAL_PUBLIC_FILES="sites/default/files"
ENV DRUPAL_PUBLIC_FILES=${DRUPAL_PUBLIC_FILES}

ARG DRUPAL_PRIVATE_FILES="sites/default/files/private"
ENV DRUPAL_PRIVATE_FILES=${DRUPAL_PRIVATE_FILES}

ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}

ARG DRUPAL_THEME="your_site_theme"
ENV DRUPAL_THEME=${DRUPAL_THEME}

ARG VORTEX_FRONTEND_BUILD_SKIP="0"
ENV VORTEX_FRONTEND_BUILD_SKIP=${VORTEX_FRONTEND_BUILD_SKIP}

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/.composer/cache \
    SIMPLETEST_DB=mysql://drupal:drupal@database/drupal \
    SIMPLETEST_BASE_URL=http://nginx:8080 \
    SYMFONY_DEPRECATIONS_HELPER=disabled

# Allow custom PHP runtime configuration for Drush CLI commands.
# The leading colon appends to the default scan directories.
# @see https://github.com/drevops/vortex/issues/1913
ENV PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR}:/app/drush/php-ini"

# Starting from this line, Docker adds the result of each command as a
# separate layer. These layers are cached and reused when the project is
# rebuilt. Layers are only rebuilt if the files added with `ADD` have changed
# since the last build. To reduce build time, add files that rarely change
# earlier in the build process (near the top of this file).

# Add more tools.
RUN apk add --no-cache ncurses pv tzdata autoconf g++ make && \
    pecl install pcov && \
    docker-php-ext-enable pcov && \
    docker-php-ext-install pcntl && \
    apk del g++ make autoconf

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

# Install PHP dependencies without development packages to avoid exposing
# potential security vulnerabilities in the production environment.
# hadolint ignore=SC2155
RUN --mount=type=secret,id=package_token \
    token=$(if [ -s /run/secrets/package_token ]; then cat /run/secrets/package_token; else echo "${PACKAGE_TOKEN}"; fi) && \
    if [ -n "${token}" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${token}\"}}"; fi && \
    COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader

# Copy all files into the application source directory. Existing files are
# always overwritten.
COPY . /app

# Create file directories and set correct permissions.
RUN mkdir -p -m 2775 "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}"

#;< DRUPAL_THEME
RUN if [ "${VORTEX_FRONTEND_BUILD_SKIP}" != "1" ]; then \
      theme_path="/app/${WEBROOT}/themes/custom/${DRUPAL_THEME}"; \
      yarn --cwd="${theme_path}" install --frozen-lockfile --no-progress && \
      yarn --cwd="${theme_path}" run build && \
      yarn cache clean; \
    fi
#;> DRUPAL_THEME

WORKDIR /app
