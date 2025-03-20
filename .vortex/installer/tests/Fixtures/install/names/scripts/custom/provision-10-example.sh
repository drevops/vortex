@@ -28,7 +28,7 @@
   # Below are examples of running operations.
 
   # Set site name.
-  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
+  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'New hope')->save();"
 
   # Enable contrib modules.
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy
@@ -44,7 +44,7 @@
   #
   # Note that deployment hooks for already enabled modules have run in the
   # parent "provision.sh" script.
-  drush pm:install sw_core sw_search
+  drush pm:install the_force_core the_force_search
   drush deploy:hook
 
   # Conditionally perform an action if this is a "fresh" database.
