@@ -57,8 +57,6 @@
   task "Installing custom site modules."
   drush pm:install sw_base
 
-  drush pm:install sw_search
-
   drush pm:install sw_demo
 
   task "Running deployment hooks."
