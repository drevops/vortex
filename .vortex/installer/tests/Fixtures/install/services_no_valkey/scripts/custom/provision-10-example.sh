@@ -42,9 +42,6 @@
   task "Installing contrib modules."
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect robotstxt shield stage_file_proxy
 
-  task "Installing Redis module."
-  drush pm:install redis || true
-
   task "Installing and configuring ClamAV."
   drush pm:install clamav
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
