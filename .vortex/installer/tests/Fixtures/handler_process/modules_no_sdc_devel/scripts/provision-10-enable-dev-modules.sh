@@ -37,10 +37,6 @@
   exit 0
 fi
 
-task "Installing Single Directory Component development tools."
-drush pm:install sdc_devel || true
-pass "Installed Single Directory Component development tools."
-
 task "Installing Devel module."
 drush pm:install devel || true
 pass "Installed Devel module."
