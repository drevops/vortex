@@ -55,10 +55,6 @@
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
   pass "Installed and configured ClamAV."
 
-  task "Installing Solr search modules."
-  drush pm:install search_api search_api_solr
-  pass "Installed Solr search modules."
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
@@ -65,8 +61,6 @@
   # parent "provision.sh" script.
   task "Installing custom site modules."
   drush pm:install sw_base
-
-  drush pm:install sw_search
 
   drush pm:install sw_demo
   pass "Installed custom site modules."
