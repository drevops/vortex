@@ -47,9 +47,6 @@
   drush pm:install clamav
   drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
 
-  task "Installing Solr search modules."
-  drush pm:install search_api search_api_solr
-
   # Enable custom site module and run its deployment hooks.
   #
   # Note that deployment hooks for already enabled modules have run in the
@@ -56,8 +53,6 @@
   # parent "provision.sh" script.
   task "Installing custom site modules."
   drush pm:install sw_base
-
-  drush pm:install sw_search
 
   task "Running deployment hooks."
   drush deploy:hook
