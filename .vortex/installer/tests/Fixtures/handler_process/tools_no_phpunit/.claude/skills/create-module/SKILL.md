@@ -82,8 +82,6 @@
 - Values that differ per environment go in a settings file, `web/sites/default/includes/modules/settings.sw_base.php`, which `settings.php` loads on its own. Read them from environment variables with the `DRUPAL_` prefix. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
 - Export configuration changes with the `manage-config` skill.
 
-- When you add a settings file, add its assertions to the settings tests in `tests/phpunit/Drupal/`.
-
 ## Choose an update hook or a deploy hook
 
 | Hook                      | File                      | Run by                                           | Use for                                                                        |
@@ -139,12 +137,6 @@
 ## Add tests
 
 Read `docs/testing.md` for the testing conventions this project agreed on. Put each test where its tool collects it.
-
-- Put PHP tests in the module's `tests/src/Unit`, `tests/src/Kernel`, `tests/src/Functional` and `tests/src/FunctionalJavascript` directories, with the namespace `Drupal\Tests\sw_base\<Type>`. `phpunit.xml` collects these directories from every custom module, so a new module needs no registration.
-- Run them with `ahoy test-unit`, `ahoy test-kernel`, `ahoy test-functional` and `ahoy test-functional-javascript`.
-- Use the `write-tests` skill to decide what to test and how.
-
-- `web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase`, `SwBaseFunctionalTestBase` and `SwBaseFunctionalJavascriptTestBase`, plus helper traits in `tests/src/Traits/`. Tests in other modules extend them by importing them from `Drupal\Tests\sw_base\<Type>`.
 
 - Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.
 
