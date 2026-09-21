@@ -36,7 +36,7 @@
 ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
 ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}
 
-ARG DRUPAL_THEME="star_wars"
+ARG DRUPAL_THEME="star_wars_theme"
 ENV DRUPAL_THEME=${DRUPAL_THEME}
 
 ARG VORTEX_FRONTEND_BUILD_SKIP="0"
@@ -83,18 +83,6 @@
     if [ -n "${token}" ]; then export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${token}\"}}"; fi && \
     COMPOSER_MEMORY_LIMIT=-1 composer install -n --no-dev --ansi --prefer-dist --optimize-autoloader
 
-# Copy files required for resolving the theme's Node.js dependencies. Placed
-# before the full source copy so changes elsewhere in the codebase do not
-# invalidate the install layer.
-COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/package-lock.json ${WEBROOT}/themes/custom/${DRUPAL_THEME}/.npmrc /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/
-COPY ${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches /app/${WEBROOT}/themes/custom/${DRUPAL_THEME}/patches
-
-RUN if [ "${VORTEX_FRONTEND_BUILD_SKIP}" != "1" ]; then \
-      export npm_config_cache=/tmp/npm-cache; \
-      npm --prefix="/app/${WEBROOT}/themes/custom/${DRUPAL_THEME}" ci --no-progress --no-audit --no-fund && \
-      rm -rf /tmp/npm-cache; \
-    fi
-
 # Copy all files into the application source directory. Existing files are
 # always overwritten.
 COPY . /app
@@ -102,10 +90,5 @@
 # Create file directories and set correct permissions.
 # hadolint ignore=SC2174 # only the leaf directory needs the mode
 RUN mkdir -p -m 2775 "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}"
-
-# hadolint ignore=DL3059 # the theme build is optional and fenced apart from the directory setup
-RUN if [ "${VORTEX_FRONTEND_BUILD_SKIP}" != "1" ]; then \
-      npm --prefix="/app/${WEBROOT}/themes/custom/${DRUPAL_THEME}" run build; \
-    fi
 
 WORKDIR /app
