@@ -57,10 +57,6 @@
   drush pm:install search_api search_api_solr
   pass "Installed Solr search modules."
 
-  task "Installing Single Directory Component development tools."
-  drush pm:install sdc_devel || true
-  pass "Installed Single Directory Component development tools."
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
