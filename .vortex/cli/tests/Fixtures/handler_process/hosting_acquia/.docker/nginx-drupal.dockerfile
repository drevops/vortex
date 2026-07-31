@@ -11,7 +11,7 @@
 FROM uselagoon/nginx-drupal:__VERSION__
 
 # Webroot is used for Nginx web root configuration.
-ARG WEBROOT=web
+ARG WEBROOT=docroot
 ENV WEBROOT=${WEBROOT}
 
 # hadolint ignore=DL3018 # the package set tracks the pinned base image
