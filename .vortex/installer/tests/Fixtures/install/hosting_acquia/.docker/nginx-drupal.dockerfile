@@ -12,7 +12,7 @@
 FROM uselagoon/nginx-drupal:25.2.0
 
 # Webroot is used for Nginx docroot configuration.
-ARG WEBROOT=web
+ARG WEBROOT=docroot
 ENV WEBROOT=${WEBROOT}
 
 RUN apk add --no-cache tzdata
