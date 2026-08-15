@@ -41,10 +41,6 @@
 drush pm:install sdc_devel || true
 pass "Installed Single Directory Component development tools."
 
-task "Installing Devel module."
-drush pm:install devel || true
-pass "Installed Devel module."
-
 task "Installing Testmode module."
 drush pm:install testmode
 pass "Installed Testmode module."
