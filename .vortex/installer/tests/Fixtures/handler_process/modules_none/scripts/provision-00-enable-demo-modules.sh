@@ -62,27 +62,6 @@
 fi
 pass "Set up the administration navigation."
 
-task "Installing contrib modules."
-drush pm:install coffee config_split config_update media diff entity_usage environment_indicator field_group linkit menu_trail_by_path metatag navigation_extra_tools pathauto redirect rename_admin_paths robotstxt scheduled_transitions schema_metatag shield stage_file_proxy views_bulk_operations xmlsitemap
-pass "Installed contrib modules."
-
-# The recipe installs the module, so only the sample headers it imports are
-# created. The others include an empty 'Content-Security-Policy', which strips
-# the policy another module sends.
-task "Installing HTTP response headers."
-# Guard against environments where the Drupal CLI is not available.
-if [ -x ./vendor/bin/dr ]; then
-  ./vendor/bin/dr recipe "$(pwd)/recipes/http_response_headers" --no-interaction
-  pass "Installed HTTP response headers."
-else
-  pass "Skipped installing HTTP response headers: Drupal CLI is not available."
-fi
-
-task "Setting Gin as the administration theme."
-drush theme:install gin
-drush config:set system.theme admin gin
-pass "Set Gin as the administration theme."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
