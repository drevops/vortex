@@ -57,10 +57,6 @@
   drush pm:uninstall toolbar
   pass "Set up the administration navigation."
 
-  task "Installing contrib modules."
-  drush pm:install coffee config_split config_update media environment_indicator navigation_extra_tools pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
-  pass "Installed contrib modules."
-
   task "Installing Redis module."
   drush pm:install redis || true
   pass "Installed Redis module."
@@ -73,14 +69,6 @@
   task "Installing Solr search modules."
   drush pm:install search_api search_api_solr
   pass "Installed Solr search modules."
-
-  task "Installing Single Directory Component development tools."
-  drush pm:install sdc_devel || true
-  pass "Installed Single Directory Component development tools."
-
-  task "Installing Devel module."
-  drush pm:install devel || true
-  pass "Installed Devel module."
 
   # Enable custom site module and run its deployment hooks.
   #
