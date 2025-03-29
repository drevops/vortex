@@ -38,8 +38,6 @@
   drush pm:install clamav
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
 
-  drush pm:install search_api search_api_solr
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
