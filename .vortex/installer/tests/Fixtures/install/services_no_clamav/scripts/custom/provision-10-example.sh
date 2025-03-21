@@ -35,9 +35,6 @@
 
   drush pm:install redis || true
 
-  drush pm:install clamav
-  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
-
   drush pm:install search_api search_api_solr
 
   # Enable custom site module and run its deployment hooks.
