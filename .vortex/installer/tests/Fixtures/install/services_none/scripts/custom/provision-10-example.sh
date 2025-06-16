@@ -42,16 +42,6 @@
   task "Installing contrib modules."
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy
 
-  task "Installing Redis module."
-  drush pm:install redis || true
-
-  task "Installing and configuring ClamAV."
-  drush pm:install clamav
-  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
-
-  task "Installing Solr search modules."
-  drush pm:install search_api search_api_solr
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
