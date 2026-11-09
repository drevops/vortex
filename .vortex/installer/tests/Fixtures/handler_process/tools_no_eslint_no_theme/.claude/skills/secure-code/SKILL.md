@@ -119,8 +119,6 @@
 
 - Audit the root JavaScript packages with `ahoy cli "npm audit"`.
 
-- Audit the custom theme's packages with `ahoy cli "npm audit --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`.
-
 - Apply an upstream security fix to a contributed package as a patch through `cweagans/composer-patches`, with the patch file in `patches/`. Never edit files under `vendor/` or `web/modules/contrib/` directly.
 
 ## Finish
