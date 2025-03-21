@@ -76,21 +76,6 @@
 RUN if [ -n "${GITHUB_TOKEN}" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN}\"}}"; fi && \
     COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader
 
-# Install NodeJS dependencies.
-# Note that package-lock.json is not explicitly copied, allowing to run the
-# stack without existing lock file (this is not advisable, but allows to build
-# using latest versions of packages). package-lock.json should be comitted to
-# the repository.
-# File Gruntfile.js is copied into image as it is required to generate
-# front-end assets.
-COPY ${WEBROOT}/themes/custom/star_wars/Gruntfile.js ${WEBROOT}/themes/custom/star_wars/.eslintrc.json ${WEBROOT}/themes/custom/star_wars/package.json ${WEBROOT}/themes/custom/star_wars/package* /app/${WEBROOT}/themes/custom/star_wars/
-COPY ${WEBROOT}/themes/custom/star_wars/patches /app/${WEBROOT}/themes/custom/star_wars/patches
-
-# Install NodeJS dependencies.
-# Since Drupal does not use NodeJS for production, it does not matter if we
-# install development dependencnies here - they are not exposed in any way.
-RUN npm --prefix /app/${WEBROOT}/themes/custom/star_wars ci --no-audit --no-progress --unsafe-perm
-
 # Copy all files into appllication source directory. Existing files are always
 # overridden.
 COPY . /app
@@ -98,10 +83,5 @@
 # Create files directories and set correct permissions.
 RUN mkdir -p "${DRUPAL_PUBLIC_FILES:-/app/${WEBROOT}/sites/default/files}" "${DRUPAL_PRIVATE_FILES:-/app/${WEBROOT}/sites/default/files/private}" "${DRUPAL_TEMPORARY_FILES:-/tmp}" "${DRUPAL_CONFIG_PATH:-/app/config/default}" && \
  chmod 0770 "${DRUPAL_PUBLIC_FILES:-/app/${WEBROOT}/sites/default/files}" "${DRUPAL_PRIVATE_FILES:-/app/${WEBROOT}/sites/default/files/private}" "${DRUPAL_TEMPORARY_FILES:-/tmp}" "${DRUPAL_CONFIG_PATH:-/app/config/default}"
-
-# Compile front-end assets. Running this after copying all files as we need
-# sources to compile assets.
-WORKDIR /app/${WEBROOT}/themes/custom/star_wars
-RUN npm run build
 
 WORKDIR /app
