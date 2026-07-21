@@ -81,10 +81,6 @@
   drush pm:install sdc_devel || true
   pass "Installed Single Directory Component development tools."
 
-  task "Installing Devel module."
-  drush pm:install devel || true
-  pass "Installed Devel module."
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
