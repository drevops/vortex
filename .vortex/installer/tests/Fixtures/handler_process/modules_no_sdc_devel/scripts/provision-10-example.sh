@@ -63,10 +63,6 @@
   drush pm:install search_api search_api_solr
   pass "Installed Solr search modules."
 
-  task "Installing Single Directory Component development tools."
-  drush pm:install sdc_devel || true
-  pass "Installed Single Directory Component development tools."
-
   task "Installing Devel module."
   drush pm:install devel || true
   pass "Installed Devel module."
