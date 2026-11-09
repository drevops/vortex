@@ -42,7 +42,7 @@
 
 Climb only as high as the assertion needs. Each step up costs more time on every run, and more setup to keep working. Prefer many cheap tests for the logic and 1 expensive test for the wiring.
 
-| Type                  | Base class                                            | Directory under `web/modules/custom/<module>/` | Use for                                                                                     |
+| Type                  | Base class                                            | Directory under `docroot/modules/custom/<module>/` | Use for                                                                                     |
 |-----------------------|-------------------------------------------------------|------------------------------------------------|---------------------------------------------------------------------------------------------|
 | Unit                  | `Drupal\Tests\UnitTestCase`                           | `tests/src/Unit`                               | A class whose collaborators can be mocked: no database, no container                         |
 | Kernel                | `Drupal\KernelTests\KernelTestBase`                   | `tests/src/Kernel`                             | Services, entities, fields, plugins, configuration and queries against a real database      |
@@ -78,12 +78,12 @@
 ahoy test-kernel
 ahoy test-functional
 ahoy test-functional-javascript
-ahoy test web/modules/custom/<module>/tests/src/Kernel/<Name>Test.php
+ahoy test docroot/modules/custom/<module>/tests/src/Kernel/<Name>Test.php
 ahoy test -- --filter=<testMethodName>
 ahoy test -- --group=<group>
 ```
 
-`ahoy test` runs all 4 suites. The `unit` suite also collects the project-level tests in `tests/phpunit`. When you change `web/sites/default/settings.php` or a file it includes, update the settings tests in `tests/phpunit/Drupal/` in the same change.
+`ahoy test` runs all 4 suites. The `unit` suite also collects the project-level tests in `tests/phpunit`. When you change `docroot/sites/default/settings.php` or a file it includes, update the settings tests in `tests/phpunit/Drupal/` in the same change.
 
 ### What the strict configuration means for you
 
@@ -100,7 +100,7 @@
 
 ### Shared base classes
 
-`web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase` and `SwBaseFunctionalTestBase`. They add the `ArrayTrait`, `AssertTrait`, `MockTrait` and `ReflectionTrait` helpers from `tests/src/Traits/`, and the functional base sets the `stark` theme. Read the traits before writing a helper of your own, and extend these bases when a test needs them.
+`docroot/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase` and `SwBaseFunctionalTestBase`. They add the `ArrayTrait`, `AssertTrait`, `MockTrait` and `ReflectionTrait` helpers from `tests/src/Traits/`, and the functional base sets the `stark` theme. Read the traits before writing a helper of your own, and extend these bases when a test needs them.
 
 ### Data providers
 
