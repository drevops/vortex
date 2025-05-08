@@ -33,8 +33,6 @@
   # Enable contrib modules.
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy
 
-  drush pm:install redis || true
-
   drush pm:install clamav
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
 
