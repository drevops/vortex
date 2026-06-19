@@ -40,10 +40,6 @@
   drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
   pass "Set site name."
 
-  task "Installing contrib modules."
-  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
-  pass "Installed contrib modules."
-
   task "Installing Redis module."
   drush pm:install redis || true
   pass "Installed Redis module."
@@ -58,7 +54,7 @@
   pass "Installed Solr search modules."
 
   task "Installing Single Directory Component development tools."
-  drush pm:install sdc_devel || true
+  drush pm:install || true
   pass "Installed Single Directory Component development tools."
 
   # Enable custom site module and run its deployment hooks.
