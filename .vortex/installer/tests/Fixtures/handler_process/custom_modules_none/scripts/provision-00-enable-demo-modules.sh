@@ -36,15 +36,6 @@
   exit 0
 fi
 
-# Site modules attach behaviour to the 'page' content type, so it must exist
-# before those modules are installed and their deploy hooks run.
-task "Creating the content model."
-# Guard against environments where the Drupal CLI is not available.
-if [ -x ./vendor/bin/dr ]; then
-  ./vendor/bin/dr recipe "$(pwd)/recipes/page" --no-interaction
-fi
-pass "Created the content model."
-
 task "Setting site name."
 drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
 pass "Set site name."
@@ -82,11 +73,7 @@
 # Note that deployment hooks for already enabled modules have run in the
 # parent "provision.sh" script.
 task "Installing custom site modules."
-drush pm:install sw_base
 
-drush pm:install sw_search
-
-drush pm:install sw_demo
 pass "Installed custom site modules."
 
 task "Running deployment hooks."
