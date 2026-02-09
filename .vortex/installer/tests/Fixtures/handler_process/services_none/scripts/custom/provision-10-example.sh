@@ -40,16 +40,6 @@
   task "Installing contrib modules."
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect robotstxt shield stage_file_proxy xmlsitemap
 
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
@@ -56,8 +46,6 @@
   # parent "provision.sh" script.
   task "Installing custom site modules."
   drush pm:install sw_base
-
-  drush pm:install sw_search
 
   task "Running deployment hooks."
   drush deploy:hook
