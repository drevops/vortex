@@ -66,11 +66,6 @@
 drush pm:install coffee config_split config_update media diff entity_usage environment_indicator field_group linkit menu_trail_by_path metatag navigation_extra_tools pathauto redirect robotstxt scheduled_transitions schema_metatag shield stage_file_proxy views_bulk_operations xmlsitemap
 pass "Installed contrib modules."
 
-task "Setting Gin as the administration theme."
-drush theme:install gin
-drush config:set system.theme admin gin
-pass "Set Gin as the administration theme."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
