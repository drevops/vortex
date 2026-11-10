@@ -22,8 +22,6 @@
 
 - `sw_base` is the module for general-purpose code that belongs to no feature module. It ships `sw_base.deploy.php`, whose `sw_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.
 
-- `sw_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.
-
 ## Name and place the module
 
 - Create the module in `web/modules/custom/<prefix>_<feature>/`. The directory, the `.info.yml` file and every other file name start with the machine name.
@@ -53,8 +51,6 @@
 - In an existing module, follow the style it already uses. Procedural hooks live in the `.module` file and are named `sw_base_<hook>()`.
 - Some hooks stay procedural: `hook_install()`, `hook_uninstall()`, `hook_schema()` and `hook_update_N()` in the `.install` file, `hook_post_update_NAME()` and Drush's `hook_deploy_NAME()`.
 - Rebuild caches after adding or removing a hook: `ahoy drush cr`.
-
-- `sw_demo` implements `hook_theme()` procedurally in `sw_demo.module`.
 
 - `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
 
