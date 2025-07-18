@@ -31,9 +31,6 @@
 ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
 ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}
 
-ARG DRUPAL_THEME=""
-ENV DRUPAL_THEME=${DRUPAL_THEME}
-
 ENV COMPOSER_ALLOW_SUPERUSER=1 \
     COMPOSER_CACHE_DIR=/tmp/.composer/cache \
     SIMPLETEST_DB=mysql://drupal:drupal@database/drupal \
@@ -79,12 +76,5 @@
 # Create file directories and set correct permissions.
 RUN mkdir -p "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" && \
  chmod 0770 "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}"
-
-RUN if [ -n "${DRUPAL_THEME}" ]; then \
-      theme_path="/app/${WEBROOT}/themes/custom/${DRUPAL_THEME}"; \
-      yarn --cwd="${theme_path}" install --frozen-lockfile --no-progress && \
-      yarn --cwd="${theme_path}" run build && \
-      yarn cache clean; \
-    fi
 
 WORKDIR /app
