@@ -10,9 +10,6 @@
 set -eu
 [ "${VORTEX_DEBUG-}" = "1" ] && set -x
 
-# Skip content generation.
-DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"
-
 # ------------------------------------------------------------------------------
 
 # @formatter:off
@@ -37,29 +34,19 @@
   exit 0
 fi
 
-task "Installing Single Directory Component development tools."
-drush pm:install sdc_devel
-pass "Installed Single Directory Component development tools."
+task "Installing Storybook module."
+drush pm:install storybook
+pass "Installed Storybook module."
 
-task "Installing Devel module."
-drush pm:install devel
-pass "Installed Devel module."
-
-task "Installing Testmode module."
-drush pm:install testmode
-pass "Installed Testmode module."
-
-task "Installing Reroute Email module."
-drush pm:install reroute_email
-pass "Installed Reroute Email module."
-
-task "Installing Generated content module."
-if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
-  note "Content generation skipped. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
-  drush pm:install generated_content
-else
-  GENERATED_CONTENT_CREATE=1 drush pm:install generated_content
+# Storybook renders every story through Drupal, so recompiling templates on
+# each request shows component edits without a cache rebuild. Only the
+# development settings change: 'drush theme:dev' also writes aggregation
+# settings that would appear in exported configuration.
+if [ "${environment}" = "local" ]; then
+  task "Enabling Twig development mode."
+  drush php:eval "\Drupal::keyValue('development_settings')->setMultiple(['twig_debug' => TRUE, 'twig_cache_disable' => TRUE, 'disable_rendered_output_cache_bins' => TRUE]);"
+  drush cache:rebuild
+  pass "Enabled Twig development mode."
 fi
-pass "Installed Generated content module."
 
 info "Finished development modules operations."
