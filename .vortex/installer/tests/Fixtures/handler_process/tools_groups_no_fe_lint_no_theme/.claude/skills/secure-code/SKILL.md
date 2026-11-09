@@ -118,8 +118,6 @@
 - Ignore an advisory only after assessing it: add its ID under `config.policy.advisories.ignore-id` in `composer.json` with the reason, and repeat the reason in the commit message. See [Dependency audit](https://www.vortextemplate.com/docs/development/security/dependency-audit).
 - Audit the root JavaScript packages with `ahoy cli "npm audit"`.
 
-- Audit the custom theme's packages with `ahoy cli "npm audit --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`.
-
 - Apply an upstream security fix to a contributed package as a patch through `cweagans/composer-patches`, with the patch file in `patches/`. Never edit files under `vendor/` or `web/modules/contrib/` directly.
 
 ## Finish
