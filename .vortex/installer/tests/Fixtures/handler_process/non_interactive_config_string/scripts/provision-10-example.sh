@@ -48,11 +48,6 @@
   drush pm:install redis || true
   pass "Installed Redis module."
 
-  task "Installing and configuring ClamAV."
-  drush pm:install clamav
-  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
-  pass "Installed and configured ClamAV."
-
   task "Installing Solr search modules."
   drush pm:install search_api search_api_solr
   pass "Installed Solr search modules."
