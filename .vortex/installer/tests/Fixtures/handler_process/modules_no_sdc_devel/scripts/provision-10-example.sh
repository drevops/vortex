@@ -58,7 +58,7 @@
   pass "Installed Solr search modules."
 
   task "Installing Single Directory Component development tools."
-  drush pm:install sdc_devel || true
+  drush pm:install || true
   pass "Installed Single Directory Component development tools."
 
   # Enable custom site module and run its deployment hooks.
