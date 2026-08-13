@@ -10,9 +10,6 @@
 set -eu
 [ "${VORTEX_DEBUG-}" = "1" ] && set -x
 
-# Skip content generation.
-DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"
-
 # ------------------------------------------------------------------------------
 
 # @formatter:off
@@ -25,46 +22,6 @@
 
 drush() { ./vendor/bin/drush -y "$@"; }
 
-##
-# Create content from every Generated content plugin the site provides.
-#
-generate_content() {
-  note "Fresh database: ${VORTEX_PROVISION_OVERRIDE_DB:-0}"
-  note "Content generation skip: ${DRUPAL_GENERATED_CONTENT_SKIP}"
-
-  if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
-    note "Skipped content generation. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
-    return 0
-  fi
-
-  if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" != "1" ]; then
-    note "Existing database detected. Skipped content generation to keep the existing content."
-    return 0
-  fi
-
-  # The module is a dependency of the demo module rather than a standalone
-  # selection, so it can be absent from a site that keeps the package.
-  if [ "$(drush php:eval "print \Drupal::moduleHandler()->moduleExists('generated_content');")" != "1" ]; then
-    note "Skipped content generation: the Generated content module is not enabled."
-    return 0
-  fi
-
-  # The module generates from hook_modules_installed(), which cannot fire for
-  # modules installed earlier in provisioning, so the repository is called
-  # directly. A non-empty repository means the imported database already carries
-  # generated content and a second run would duplicate it.
-  if [ "$(drush php:eval "print \Drupal\generated_content\GeneratedContentRepository::getInstance()->isEmpty() ? 1 : 0;")" != "1" ]; then
-    note "Skipped content generation: generated content already exists."
-    return 0
-  fi
-
-  # Generation is heavier than the rest of provisioning and exceeds the limit
-  # set in drush/php-ini/drush.ini.
-  task "Generating content."
-  drush php:eval "ini_set('memory_limit', '-1'); \Drupal\generated_content\GeneratedContentRepository::getInstance()->createEntities();"
-  pass "Generated content."
-}
-
 # ------------------------------------------------------------------------------
 
 info "Started development modules operations."
@@ -84,7 +41,5 @@
 task "Installing Devel module."
 drush pm:install devel || true
 pass "Installed Devel module."
-
-generate_content
 
 info "Finished development modules operations."
