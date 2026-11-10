@@ -71,10 +71,6 @@
 drush config:set system.theme admin gin
 pass "Set Gin as the administration theme."
 
-task "Installing Redis module."
-drush pm:install redis || true
-pass "Installed Redis module."
-
 task "Installing and configuring ClamAV."
 drush pm:install clamav
 drush config:set clamav.settings mode_daemon_tcpip.hostname clamav
