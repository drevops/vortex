@@ -83,13 +83,13 @@
 # the repository.
 # File Gruntfile.js is copied into image as it is required to generate
 # front-end assets.
-COPY ${WEBROOT}/themes/custom/star_wars/Gruntfile.js ${WEBROOT}/themes/custom/star_wars/.eslintrc.json ${WEBROOT}/themes/custom/star_wars/package.json ${WEBROOT}/themes/custom/star_wars/package* /app/${WEBROOT}/themes/custom/star_wars/
-COPY ${WEBROOT}/themes/custom/star_wars/patches /app/${WEBROOT}/themes/custom/star_wars/patches
+COPY ${WEBROOT}/themes/custom/lightsaber/Gruntfile.js ${WEBROOT}/themes/custom/lightsaber/.eslintrc.json ${WEBROOT}/themes/custom/lightsaber/package.json ${WEBROOT}/themes/custom/lightsaber/package* /app/${WEBROOT}/themes/custom/lightsaber/
+COPY ${WEBROOT}/themes/custom/lightsaber/patches /app/${WEBROOT}/themes/custom/lightsaber/patches
 
 # Install NodeJS dependencies.
 # Since Drupal does not use NodeJS for production, it does not matter if we
 # install development dependencnies here - they are not exposed in any way.
-RUN npm --prefix /app/${WEBROOT}/themes/custom/star_wars ci --no-audit --no-progress --unsafe-perm
+RUN npm --prefix /app/${WEBROOT}/themes/custom/lightsaber ci --no-audit --no-progress --unsafe-perm
 
 # Copy all files into appllication source directory. Existing files are always
 # overridden.
@@ -101,7 +101,7 @@
 
 # Compile front-end assets. Running this after copying all files as we need
 # sources to compile assets.
-WORKDIR /app/${WEBROOT}/themes/custom/star_wars
+WORKDIR /app/${WEBROOT}/themes/custom/lightsaber
 RUN npm run build
 
 WORKDIR /app
