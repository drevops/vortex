@@ -16,7 +16,7 @@
 ENV LAGOON_PR_HEAD_SHA=${LAGOON_PR_HEAD_SHA}
 
 # Webroot is used for Drush aliases.
-ARG WEBROOT=web
+ARG WEBROOT=docroot
 
 ARG GITHUB_TOKEN=""
 ENV GITHUB_TOKEN=${GITHUB_TOKEN}
