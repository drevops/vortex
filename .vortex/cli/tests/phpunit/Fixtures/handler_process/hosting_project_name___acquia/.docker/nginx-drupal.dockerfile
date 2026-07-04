@@ -14,7 +14,7 @@
 FROM uselagoon/nginx-drupal:__VERSION__
 
 # Webroot is used for Nginx web root configuration.
-ARG WEBROOT=web
+ARG WEBROOT=docroot
 ENV WEBROOT=${WEBROOT}
 
 RUN apk add --no-cache tzdata
