@@ -37,9 +37,6 @@
   task "Setting site name."
   drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
 
-  task "Installing contrib modules."
-  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect robotstxt shield stage_file_proxy xmlsitemap
-
   task "Installing Redis module."
   drush pm:install redis || true
 
