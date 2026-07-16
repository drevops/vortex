@@ -46,7 +46,7 @@
   pass "Created the content model."
 
   task "Setting site name."
-  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
+  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'New hope')->save();"
   pass "Set site name."
 
   # Use the core Navigation module as the administration interface. The
@@ -87,11 +87,11 @@
   # Note that deployment hooks for already enabled modules have run in the
   # parent "provision.sh" script.
   task "Installing custom site modules."
-  drush pm:install sw_base
+  drush pm:install the_force_base
 
-  drush pm:install sw_search
+  drush pm:install the_force_search
 
-  drush pm:install sw_demo
+  drush pm:install the_force_demo
   pass "Installed custom site modules."
 
   task "Running deployment hooks."
