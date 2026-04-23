@@ -43,19 +43,6 @@
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
   pass "Installed contrib modules."
 
-  task "Installing Redis module."
-  drush pm:install redis || true
-  pass "Installed Redis module."
-
-  task "Installing and configuring ClamAV."
-  drush pm:install clamav
-  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
-  pass "Installed and configured ClamAV."
-
-  task "Installing Solr search modules."
-  drush pm:install search_api search_api_solr
-  pass "Installed Solr search modules."
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
@@ -62,8 +49,6 @@
   # parent "provision.sh" script.
   task "Installing custom site modules."
   drush pm:install sw_base
-
-  drush pm:install sw_search
 
   drush pm:install sw_demo
   pass "Installed custom site modules."
