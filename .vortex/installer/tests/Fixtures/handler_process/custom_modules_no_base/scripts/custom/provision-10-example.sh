@@ -61,7 +61,6 @@
   # Note that deployment hooks for already enabled modules have run in the
   # parent "provision.sh" script.
   task "Installing custom site modules."
-  drush pm:install sw_base
 
   drush pm:install sw_search
 
