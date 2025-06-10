@@ -31,9 +31,6 @@
 ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
 ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}
 
-ARG DRUPAL_THEME="star_wars"
-ENV DRUPAL_THEME=${DRUPAL_THEME}
-
 ENV COMPOSER_ALLOW_SUPERUSER=1 \
     COMPOSER_CACHE_DIR=/tmp/.composer/cache \
     SIMPLETEST_DB=mysql://drupal:drupal@database/drupal \
@@ -71,21 +68,6 @@
 RUN if [ -n "${GITHUB_TOKEN}" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN}\"}}"; fi && \
     COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader
 
-# Install NodeJS dependencies.
-# Note that yarn.lock is not explicitly copied, allowing the stack to
-# run without an existing lock file. This is not recommended but enables builds
-# using the latest package versions. The yarn.lock file should be
-# committed to the repository.
-# Gruntfile.js is copied into the image as it is required to generate front-end
-# assets.
-COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/Gruntfile.js ${WEBROOT}/themes/custom/${DRUPAL_THEME}/.eslintrc.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/yarn* /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/
-COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches
-
-# Install NodeJS dependencies.
-# Since Drupal does not use NodeJS in production, installing development
-# dependencies here is fine — they are not exposed in any way.
-RUN yarn --cwd=/app/${WEBROOT}/themes/custom/${DRUPAL_THEME} install --frozen-lock --no-progress && yarn cache clean
-
 # Copy all files into the application source directory. Existing files are
 # always overwritten.
 COPY . /app
@@ -93,9 +75,5 @@
 # Create file directories and set correct permissions.
 RUN mkdir -p "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" && \
  chmod 0770 "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}"
-
-# Compile front-end assets. This runs after copying all files, as source files
-# are needed for compilation.
-RUN yarn --cwd=/app/${WEBROOT}/themes/custom/${DRUPAL_THEME} run build
 
 WORKDIR /app
