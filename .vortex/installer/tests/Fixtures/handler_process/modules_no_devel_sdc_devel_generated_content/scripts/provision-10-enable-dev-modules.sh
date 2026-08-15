@@ -10,9 +10,6 @@
 set -eu
 [ "${VORTEX_DEBUG-}" = "1" ] && set -x
 
-# Skip content generation.
-DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"
-
 # ------------------------------------------------------------------------------
 
 # @formatter:off
@@ -37,25 +34,8 @@
   exit 0
 fi
 
-task "Installing Single Directory Component development tools."
-drush pm:install sdc_devel || true
-pass "Installed Single Directory Component development tools."
-
-task "Installing Devel module."
-drush pm:install devel || true
-pass "Installed Devel module."
-
 task "Installing Testmode module."
 drush pm:install testmode
 pass "Installed Testmode module."
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
