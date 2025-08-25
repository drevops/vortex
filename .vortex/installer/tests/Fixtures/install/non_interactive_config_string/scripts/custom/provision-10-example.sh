@@ -45,10 +45,6 @@
   task "Installing Redis module."
   drush pm:install redis || true
 
-  task "Installing and configuring ClamAV."
-  drush pm:install clamav
-  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
-
   task "Installing Solr search modules."
   drush pm:install search_api search_api_solr
 
