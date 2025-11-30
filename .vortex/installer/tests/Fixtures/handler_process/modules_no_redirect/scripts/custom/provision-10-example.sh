@@ -38,7 +38,7 @@
   drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
 
   task "Installing contrib modules."
-  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect robotstxt shield stage_file_proxy
+  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto robotstxt shield stage_file_proxy
 
   task "Installing Redis module."
   drush pm:install redis || true
