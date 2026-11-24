@@ -66,16 +66,6 @@
 drush pm:install coffee config_split config_update media diff entity_usage environment_indicator field_group linkit menu_trail_by_path metatag navigation_extra_tools pathauto redirect rename_admin_paths robotstxt scheduled_transitions schema_metatag shield stage_file_proxy views_bulk_operations xmlsitemap
 pass "Installed contrib modules."
 
-# The recipe installs the module, so only the sample headers it imports are
-# created. The others include an empty 'Content-Security-Policy', which strips
-# the policy another module sends.
-#
-# Applied through Drush because './vendor/bin/dr' has no request context, and
-# Entity Usage fails on the config entities the recipe creates without one.
-task "Installing HTTP response headers."
-drush php:eval "\Drupal\Core\Recipe\RecipeRunner::processRecipe(\Drupal\Core\Recipe\Recipe::createFromDirectory(DRUPAL_ROOT . '/../recipes/http_response_headers'));"
-pass "Installed HTTP response headers."
-
 task "Setting Gin as the administration theme."
 drush theme:install gin
 drush config:set system.theme admin gin
