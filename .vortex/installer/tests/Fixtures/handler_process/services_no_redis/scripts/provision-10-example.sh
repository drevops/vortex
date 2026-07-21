@@ -64,10 +64,6 @@
   drush pm:install coffee config_split config_update media environment_indicator navigation_extra_tools pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
   pass "Installed contrib modules."
 
-  task "Installing Redis module."
-  drush pm:install redis || true
-  pass "Installed Redis module."
-
   task "Installing and configuring ClamAV."
   drush pm:install clamav
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
