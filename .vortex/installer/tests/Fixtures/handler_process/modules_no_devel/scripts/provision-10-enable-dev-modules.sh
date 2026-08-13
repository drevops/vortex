@@ -41,10 +41,6 @@
 drush pm:install sdc_devel || true
 pass "Installed Single Directory Component development tools."
 
-task "Installing Devel module."
-drush pm:install devel || true
-pass "Installed Devel module."
-
 task "Installing Generated content module."
 if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
   note "Content generation skipped. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
