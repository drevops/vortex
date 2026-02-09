@@ -35,7 +35,7 @@
   note "Running example operations in non-production environment."
 
   task "Setting site name."
-  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
+  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'New hope')->save();"
 
   task "Installing contrib modules."
   drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect robotstxt shield stage_file_proxy xmlsitemap
@@ -55,9 +55,9 @@
   # Note that deployment hooks for already enabled modules have run in the
   # parent "provision.sh" script.
   task "Installing custom site modules."
-  drush pm:install sw_base
+  drush pm:install the_force_base
 
-  drush pm:install sw_search
+  drush pm:install the_force_search
 
   task "Running deployment hooks."
   drush deploy:hook
