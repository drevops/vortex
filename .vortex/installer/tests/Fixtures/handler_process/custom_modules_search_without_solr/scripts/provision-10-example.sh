@@ -59,10 +59,6 @@
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
   pass "Installed and configured ClamAV."
 
-  task "Installing Solr search modules."
-  drush pm:install search_api search_api_solr
-  pass "Installed Solr search modules."
-
   task "Installing Single Directory Component development tools."
   drush pm:install sdc_devel || true
   pass "Installed Single Directory Component development tools."
@@ -77,8 +73,6 @@
   # parent "provision.sh" script.
   task "Installing custom site modules."
   drush pm:install sw_base
-
-  drush pm:install sw_search
 
   drush pm:install sw_demo
   pass "Installed custom site modules."
