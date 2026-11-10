@@ -20,8 +20,6 @@
 - Extend an existing module when the code belongs to the feature that module already owns.
 - Create a new module for a new feature with its own configuration, routes or dependencies, so the feature can be enabled, disabled and removed on its own.
 
-- `sw_base` is the module for general-purpose code that belongs to no feature module. It ships `sw_base.deploy.php`, whose `sw_base_deploy_install_active_theme()` installs the default theme, and a test scaffold. Put a small shared helper there instead of creating a module for it.
-
 - `sw_demo` demonstrates the patterns: a block plugin with a library and a Twig template, deploy hooks and a view in `config/install`. Read it for examples, but never add production code to it, because projects remove the demo module.
 
 ## Name and place the module
@@ -143,8 +141,6 @@
 - Put PHP tests in the module's `tests/src/Unit`, `tests/src/Kernel`, `tests/src/Functional` and `tests/src/FunctionalJavascript` directories, with the namespace `Drupal\Tests\sw_base\<Type>`. `phpunit.xml` collects these directories from every custom module, so a new module needs no registration.
 - Run them with `ahoy test-unit`, `ahoy test-kernel`, `ahoy test-functional` and `ahoy test-functional-javascript`.
 - Use the `write-tests` skill to decide what to test and how.
-
-- `web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase`, `SwBaseFunctionalTestBase` and `SwBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\sw_base\<Type>`.
 
 - Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.
 
