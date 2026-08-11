@@ -47,7 +47,7 @@
 fi
 
 task "Setting site name."
-drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
+drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'New hope')->save();"
 pass "Set site name."
 
 # The core Navigation module serves as the administration interface, so the
@@ -79,11 +79,11 @@
 pass "Installed Solr search modules."
 
 task "Installing custom site modules."
-drush pm:install sw_base
+drush pm:install the_force_base
 
-drush pm:install sw_search
+drush pm:install the_force_search
 
-drush pm:install sw_demo
+drush pm:install the_force_demo
 pass "Installed custom site modules."
 
 # Deployment hooks for already enabled modules have run in the parent
