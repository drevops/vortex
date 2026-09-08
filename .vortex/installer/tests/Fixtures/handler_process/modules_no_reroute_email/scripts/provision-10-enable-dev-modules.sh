@@ -49,10 +49,6 @@
 drush pm:install testmode
 pass "Installed Testmode module."
 
-task "Installing Reroute Email module."
-drush pm:install reroute_email
-pass "Installed Reroute Email module."
-
 task "Installing Generated content module."
 if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
   note "Content generation skipped. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
