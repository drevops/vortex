@@ -36,7 +36,7 @@
 ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
 ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}
 
-ARG DRUPAL_THEME="star_wars"
+ARG DRUPAL_THEME="star_wars_theme"
 ENV DRUPAL_THEME=${DRUPAL_THEME}
 
 ARG VORTEX_FRONTEND_BUILD_SKIP="0"
@@ -94,13 +94,5 @@
 # Create file directories and set correct permissions.
 # hadolint ignore=SC2174 # only the leaf directory needs the mode
 RUN mkdir -p -m 2775 "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}"
-
-RUN if [ "${VORTEX_FRONTEND_BUILD_SKIP}" != "1" ]; then \
-      theme_path="/app/${WEBROOT}/themes/custom/${DRUPAL_THEME}"; \
-      export npm_config_cache=/tmp/npm-cache; \
-      npm --prefix="${theme_path}" ci --no-progress --no-audit --no-fund && \
-      npm --prefix="${theme_path}" run build && \
-      rm -rf /tmp/npm-cache; \
-    fi
 
 WORKDIR /app
