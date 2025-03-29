@@ -33,13 +33,6 @@
   # Enable contrib modules.
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy
 
-  drush pm:install redis || true
-
-  drush pm:install clamav
-  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
-
-  drush pm:install search_api search_api_solr
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
