@@ -45,10 +45,6 @@
 drush pm:install devel || true
 pass "Installed Devel module."
 
-task "Installing Testmode module."
-drush pm:install testmode
-pass "Installed Testmode module."
-
 task "Installing Generated content module."
 if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
   note "Content generation skipped. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
