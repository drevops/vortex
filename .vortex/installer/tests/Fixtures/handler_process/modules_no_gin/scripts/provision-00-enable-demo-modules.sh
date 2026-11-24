@@ -76,11 +76,6 @@
 drush php:eval "\Drupal\Core\Recipe\RecipeRunner::processRecipe(\Drupal\Core\Recipe\Recipe::createFromDirectory(DRUPAL_ROOT . '/../recipes/http_response_headers'));"
 pass "Installed HTTP response headers."
 
-task "Setting Gin as the administration theme."
-drush theme:install gin
-drush config:set system.theme admin gin
-pass "Set Gin as the administration theme."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
