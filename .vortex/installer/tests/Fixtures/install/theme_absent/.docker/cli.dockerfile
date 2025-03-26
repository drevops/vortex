@@ -22,9 +22,6 @@
 ARG GITHUB_TOKEN=""
 ENV GITHUB_TOKEN=${GITHUB_TOKEN}
 
-ARG DRUPAL_THEME="star_wars"
-ENV DRUPAL_THEME=${DRUPAL_THEME}
-
 ARG DRUPAL_PUBLIC_FILES="/app/${WEBROOT}/sites/default/files"
 ENV DRUPAL_PUBLIC_FILES=${DRUPAL_PUBLIC_FILES}
 
@@ -74,21 +71,6 @@
 RUN if [ -n "${GITHUB_TOKEN}" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN}\"}}"; fi && \
     COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader
 
-# Install NodeJS dependencies.
-# Note that package-lock.json is not explicitly copied, allowing the stack to
-# run without an existing lock file. This is not recommended but enables builds
-# using the latest package versions. The package-lock.json file should be
-# committed to the repository.
-# Gruntfile.js is copied into the image as it is required to generate front-end
-# assets.
-COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/Gruntfile.js ${WEBROOT}/themes/custom/${DRUPAL_THEME}/.eslintrc.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package* /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/
-COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches
-
-# Install NodeJS dependencies.
-# Since Drupal does not use NodeJS in production, installing development
-# dependencies here is fine — they are not exposed in any way.
-RUN npm --prefix /app/${WEBROOT}/themes/custom/${DRUPAL_THEME} ci --no-audit --no-progress --unsafe-perm
-
 # Copy all files into the application source directory. Existing files are
 # always overwritten.
 COPY . /app
@@ -96,10 +78,5 @@
 # Create file directories and set correct permissions.
 RUN mkdir -p "${DRUPAL_PUBLIC_FILES}" "${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" "${DRUPAL_CONFIG_PATH}" && \
  chmod 0770 "${DRUPAL_PUBLIC_FILES}" "${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" "${DRUPAL_CONFIG_PATH}"
-
-# Compile front-end assets. This runs after copying all files, as source files
-# are needed for compilation.
-WORKDIR /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}
-RUN npm run build
 
 WORKDIR /app
