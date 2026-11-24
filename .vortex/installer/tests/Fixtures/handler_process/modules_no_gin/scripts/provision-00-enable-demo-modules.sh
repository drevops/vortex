@@ -79,11 +79,6 @@
 drush php:eval "${headers_php}"
 pass "Configured HTTP response headers."
 
-task "Setting Gin as the administration theme."
-drush theme:install gin
-drush config:set system.theme admin gin
-pass "Set Gin as the administration theme."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
