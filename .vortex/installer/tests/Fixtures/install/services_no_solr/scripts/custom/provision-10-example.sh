@@ -49,9 +49,6 @@
   drush pm:install clamav
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
 
-  task "Installing Solr search modules."
-  drush pm:install search_api search_api_solr
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
