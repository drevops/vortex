@@ -37,14 +37,6 @@
   exit 0
 fi
 
-task "Installing Single Directory Component development tools."
-drush pm:install sdc_devel
-pass "Installed Single Directory Component development tools."
-
-task "Installing Devel module."
-drush pm:install devel
-pass "Installed Devel module."
-
 task "Installing Testmode module."
 drush pm:install testmode
 pass "Installed Testmode module."
