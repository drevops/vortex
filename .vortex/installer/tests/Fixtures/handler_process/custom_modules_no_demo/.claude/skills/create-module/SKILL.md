@@ -22,8 +22,6 @@
 
 - `sw_base` is the module for general-purpose code that belongs to no feature module. It ships `sw_base.deploy.php`, whose `sw_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.
 
-- `sw_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.
-
 ## Name and place the module
 
 - Create the module in `web/modules/custom/<prefix>_<feature>/`. The directory, the `.info.yml` file and every other file name start with the machine name.
@@ -54,8 +52,6 @@
 - Some hooks stay procedural: `hook_install()`, `hook_uninstall()`, `hook_schema()` and `hook_update_N()` in the `.install` file, `hook_post_update_NAME()` and Drush's `hook_deploy_NAME()`.
 - Rebuild caches after adding or removing a hook: `ahoy drush cr`.
 
-- `sw_demo` implements `hook_theme()` procedurally in `sw_demo.module`.
-
 - `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
 
 ## Add plugins
@@ -63,8 +59,6 @@
 - Declare a plugin with its PHP attribute, never with a docblock annotation. A block plugin uses `#[Block(...)]` from `Drupal\Core\Block\Attribute\Block`. Find the attribute of any other plugin type in the `Attribute` namespace of the module that defines the type, such as `web/core/lib/Drupal/Core/Field/Attribute/` for field formatters and widgets.
 - Put each plugin in the directory its manager scans, such as `src/Plugin/Block/` for blocks, and start its ID with the module name: `sw_base_latest_pages`.
 - Declare cache metadata on everything a plugin renders. Use the `debug-performance` skill when output stays stale or a page stops caching.
-
-- `CounterBlock` in `sw_demo` still declares its plugin with the `@Block` annotation. Don't copy that part.
 
 ## Add routes, controllers and forms
 
