@@ -20,10 +20,6 @@
 - Extend an existing module when the code belongs to the feature that module already owns.
 - Create a new module for a new feature with its own configuration, routes or dependencies, so the feature can be enabled, disabled and removed on its own.
 
-- `sw_base` is the module for general-purpose code that belongs to no feature module. It ships `sw_base.deploy.php`, whose `sw_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.
-
-- `sw_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.
-
 ## Name and place the module
 
 - Create the module in `web/modules/custom/<prefix>_<feature>/`. The directory, the `.info.yml` file and every other file name start with the machine name.
@@ -54,8 +50,6 @@
 - Some hooks stay procedural: `hook_install()`, `hook_uninstall()`, `hook_schema()` and `hook_update_N()` in the `.install` file, `hook_post_update_NAME()` and Drush's `hook_deploy_NAME()`.
 - Rebuild caches after adding or removing a hook: `ahoy drush cr`.
 
-- `sw_demo` implements `hook_theme()` procedurally in `sw_demo.module`.
-
 - `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
 
 ## Add plugins
@@ -64,8 +58,6 @@
 - Put each plugin in the directory its manager scans, such as `src/Plugin/Block/` for blocks, and start its ID with the module name: `sw_base_latest_pages`.
 - Declare cache metadata on everything a plugin renders. Use the `debug-performance` skill when output stays stale or a page stops caching.
 
-- `CounterBlock` in `sw_demo` still declares its plugin with the `@Block` annotation. Don't copy that part.
-
 ## Add routes, controllers and forms
 
 - Define routes in `sw_base.routing.yml`, with names that start with `sw_base.`. Give every route a `requirements` entry, usually `_permission`. Use `_access: 'TRUE'` only for a route that is intentionally public.
@@ -143,8 +135,6 @@
 - Put PHP tests in the module's `tests/src/Unit`, `tests/src/Kernel`, `tests/src/Functional` and `tests/src/FunctionalJavascript` directories, with the namespace `Drupal\Tests\sw_base\<Type>`. `phpunit.xml` collects these directories from every custom module, so a new module needs no registration.
 - Run them with `ahoy test-unit`, `ahoy test-kernel`, `ahoy test-functional` and `ahoy test-functional-javascript`.
 - Use the `write-tests` skill to decide what to test and how.
-
-- `web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase`, `SwBaseFunctionalTestBase` and `SwBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\sw_base\<Type>`.
 
 - Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.
 
