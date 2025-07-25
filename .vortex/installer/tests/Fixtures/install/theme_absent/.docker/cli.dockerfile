@@ -32,9 +32,6 @@
 ARG DRUPAL_TEMPORARY_FILES="${TMP:-/tmp}"
 ENV DRUPAL_TEMPORARY_FILES=${DRUPAL_TEMPORARY_FILES}
 
-ARG DRUPAL_THEME="star_wars"
-ENV DRUPAL_THEME=${DRUPAL_THEME}
-
 ARG VORTEX_FRONTEND_BUILD_SKIP="0"
 ENV VORTEX_FRONTEND_BUILD_SKIP=${VORTEX_FRONTEND_BUILD_SKIP}
 
@@ -85,12 +82,5 @@
 # Create file directories and set correct permissions.
 RUN mkdir -p "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}" && \
     chmod 0770 "/app/${WEBROOT}/${DRUPAL_PUBLIC_FILES}" "/app/${WEBROOT}/${DRUPAL_PRIVATE_FILES}" "${DRUPAL_TEMPORARY_FILES}"
-
-RUN if [ "${VORTEX_FRONTEND_BUILD_SKIP}" != "1" ]; then \
-      theme_path="/app/${WEBROOT}/themes/custom/${DRUPAL_THEME}"; \
-      yarn --cwd="${theme_path}" install --frozen-lockfile --no-progress && \
-      yarn --cwd="${theme_path}" run build && \
-      yarn cache clean; \
-    fi
 
 WORKDIR /app
