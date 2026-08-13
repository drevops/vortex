@@ -10,9 +10,6 @@
 set -eu
 [ "${VORTEX_DEBUG-}" = "1" ] && set -x
 
-# Skip content generation.
-DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"
-
 # ------------------------------------------------------------------------------
 
 # @formatter:off
@@ -44,15 +41,5 @@
 task "Installing Devel module."
 drush pm:install devel || true
 pass "Installed Devel module."
-
-if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
-  note "Skipped content generation. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
-else
-  # The module creates content from its own hook_modules_installed(), so the
-  # content appears as the module is installed.
-  task "Installing Generated content module."
-  GENERATED_CONTENT_CREATE=1 drush pm:install generated_content
-  pass "Installed Generated content module."
-fi
 
 info "Finished development modules operations."
