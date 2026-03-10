@@ -55,11 +55,6 @@
   # Note that deployment hooks for already enabled modules have run in the
   # parent "provision.sh" script.
   task "Installing custom site modules."
-  drush pm:install sw_base
-
-  drush pm:install sw_search
-
-  drush pm:install sw_demo
 
   task "Running deployment hooks."
   drush deploy:hook
