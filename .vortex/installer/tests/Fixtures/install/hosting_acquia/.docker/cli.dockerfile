@@ -16,7 +16,7 @@
 ARG LAGOON_PR_HEAD_SHA=""
 ENV LAGOON_PR_HEAD_SHA=${LAGOON_PR_HEAD_SHA}
 
-ARG WEBROOT=web
+ARG WEBROOT=docroot
 ENV WEBROOT=${WEBROOT}
 
 # Token is used to access private repositories. Not exposed as an environment
