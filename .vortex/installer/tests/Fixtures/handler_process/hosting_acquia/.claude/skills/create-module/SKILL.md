@@ -15,7 +15,7 @@
 
 ## Decide where the code goes
 
-List the custom modules first: `ls web/modules/custom`.
+List the custom modules first: `ls docroot/modules/custom`.
 
 - Extend an existing module when the code belongs to the feature that module already owns.
 - Create a new module for a new feature with its own configuration, routes or dependencies, so the feature can be enabled, disabled and removed on its own.
@@ -26,9 +26,9 @@
 
 ## Name and place the module
 
-- Create the module in `web/modules/custom/<prefix>_<feature>/`. The directory, the `.info.yml` file and every other file name start with the machine name.
+- Create the module in `docroot/modules/custom/<prefix>_<feature>/`. The directory, the `.info.yml` file and every other file name start with the machine name.
 - Use lowercase letters, digits and underscores, at most 50 characters. Drupal refuses to install a longer name.
-- Every custom module shares 1 prefix, `ys` in the template, which the installer replaced with the project's own. Read it from the start of an existing module directory name in `web/modules/custom/`. When none exists, ask the developer.
+- Every custom module shares 1 prefix, `ys` in the template, which the installer replaced with the project's own. Read it from the start of an existing module directory name in `docroot/modules/custom/`. When none exists, ask the developer.
 
 ## Write the info file
 
@@ -56,11 +56,11 @@
 
 - `sw_demo` implements `hook_theme()` procedurally in `sw_demo.module`.
 
-- `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
+- `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `docroot/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
 
 ## Add plugins
 
-- Declare a plugin with its PHP attribute, never with a docblock annotation. A block plugin uses `#[Block(...)]` from `Drupal\Core\Block\Attribute\Block`. Find the attribute of any other plugin type in the `Attribute` namespace of the module that defines the type, such as `web/core/lib/Drupal/Core/Field/Attribute/` for field formatters and widgets.
+- Declare a plugin with its PHP attribute, never with a docblock annotation. A block plugin uses `#[Block(...)]` from `Drupal\Core\Block\Attribute\Block`. Find the attribute of any other plugin type in the `Attribute` namespace of the module that defines the type, such as `docroot/core/lib/Drupal/Core/Field/Attribute/` for field formatters and widgets.
 - Put each plugin in the directory its manager scans, such as `src/Plugin/Block/` for blocks, and start its ID with the module name: `sw_base_latest_pages`.
 - Declare cache metadata on everything a plugin renders. Use the `debug-performance` skill when output stays stale or a page stops caching.
 
@@ -79,7 +79,7 @@
 
 - Put the module's default configuration in `config/install/`, and configuration that depends on another optional module in `config/optional/`. Drupal installs it once, when the module is installed. Existing sites receive later changes only through the exported configuration in `config/default` or through an update hook.
 - Describe every configuration item the module defines in `config/schema/sw_base.schema.yml`.
-- Values that differ per environment go in a settings file, `web/sites/default/includes/modules/settings.sw_base.php`, which `settings.php` loads on its own. Read them from environment variables with the `DRUPAL_` prefix. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
+- Values that differ per environment go in a settings file, `docroot/sites/default/includes/modules/settings.sw_base.php`, which `settings.php` loads on its own. Read them from environment variables with the `DRUPAL_` prefix. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
 - Export configuration changes with the `manage-config` skill.
 
 - When you add a settings file, add its assertions to the settings tests in `tests/phpunit/Drupal/`.
@@ -134,7 +134,7 @@
 - **Through configuration.** Once the project exports its configuration, `config/default/core.extension.yml` lists every enabled module. The configuration import during provisioning installs a newly listed module on every environment, production included, before the deploy hooks run. Export `core.extension` and the module's configuration with the `manage-config` skill. This is the path for any module production needs.
 - **Through a provision script.** The template ships `config/default` without configuration, so provisioning skips the import until the project exports it. Until then, modules come from the provision scripts: `scripts/provision-00-enable-demo-modules.sh` runs `drush pm:install` for the shipped modules. That script exits before installing anything in production, so a module installed only there never reaches production. To install a module on every environment before the configuration is exported, add your own `scripts/provision-<NN>-<name>.sh` with the environment checks it needs.
 
-A module that must never reach production, such as a development tool, stays out of the export through `$settings['config_exclude_modules'][]` in its `web/sites/default/includes/modules/settings.<module>.php` file, as the shipped development modules do.
+A module that must never reach production, such as a development tool, stays out of the export through `$settings['config_exclude_modules'][]` in its `docroot/sites/default/includes/modules/settings.<module>.php` file, as the shipped development modules do.
 
 ## Add tests
 
@@ -144,7 +144,7 @@
 - Run them with `ahoy test-unit`, `ahoy test-kernel`, `ahoy test-functional` and `ahoy test-functional-javascript`.
 - Use the `write-tests` skill to decide what to test and how.
 
-- `web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase`, `SwBaseFunctionalTestBase` and `SwBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\sw_base\<Type>`.
+- `docroot/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase`, `SwBaseFunctionalTestBase` and `SwBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\sw_base\<Type>`.
 
 - Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.
 
