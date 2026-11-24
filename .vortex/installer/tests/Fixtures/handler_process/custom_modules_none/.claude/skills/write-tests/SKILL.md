@@ -98,10 +98,6 @@
 
 See https://www.vortextemplate.com/docs/development/testing/phpunit for the configuration, reports and CI behavior.
 
-### Shared base classes
-
-`web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase` and `SwBaseFunctionalTestBase`. They add the `ArrayTrait`, `AssertTrait`, `MockTrait` and `ReflectionTrait` helpers from `tests/src/Traits/`, and the functional base sets the `stark` theme. Read the traits before writing a helper of your own, and extend these bases when a test needs them.
-
 ### Data providers
 
 Put the cases of 1 behavior in a data provider instead of copying the test method. Follow the naming and placement rules in `docs/testing.md`, and give every row a key that describes the case, so a failure names it:
