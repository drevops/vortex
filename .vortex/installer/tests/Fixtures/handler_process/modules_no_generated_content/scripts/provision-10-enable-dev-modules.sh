@@ -10,9 +10,6 @@
 set -eu
 [ "${VORTEX_DEBUG-}" = "1" ] && set -x
 
-# Skip content generation.
-DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"
-
 # ------------------------------------------------------------------------------
 
 # @formatter:off
@@ -52,14 +49,5 @@
 task "Installing Reroute Email module."
 drush pm:install reroute_email
 pass "Installed Reroute Email module."
-
-task "Installing Generated content module."
-if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
-  note "Content generation skipped. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
-  drush pm:install generated_content
-else
-  GENERATED_CONTENT_CREATE=1 drush pm:install generated_content
-fi
-pass "Installed Generated content module."
 
 info "Finished development modules operations."
