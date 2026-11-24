@@ -78,11 +78,6 @@
   pass "Skipped installing HTTP response headers: Drupal CLI is not available."
 fi
 
-task "Setting Gin as the administration theme."
-drush theme:install gin
-drush config:set system.theme admin gin
-pass "Set Gin as the administration theme."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
