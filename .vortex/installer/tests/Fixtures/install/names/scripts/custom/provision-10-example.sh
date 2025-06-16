@@ -36,7 +36,7 @@
 
   # Set site name.
   task "Setting site name."
-  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
+  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'New hope')->save();"
 
   # Enable contrib modules.
   task "Installing contrib modules."
@@ -57,7 +57,7 @@
   # Note that deployment hooks for already enabled modules have run in the
   # parent "provision.sh" script.
   task "Installing custom site modules."
-  drush pm:install sw_base sw_search
+  drush pm:install the_force_base the_force_search
 
   task "Running deployment hooks."
   drush deploy:hook
