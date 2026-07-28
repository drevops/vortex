@@ -38,7 +38,6 @@
 use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
 use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
 use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
-use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
 use Rector\Privatization\Rector\ClassConst\PrivatizeFinalClassConstantRector;
 use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
 use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
@@ -88,8 +87,6 @@
   // PHP version upgrade sets - modernizes syntax to PHP 8.4.
   // Includes all rules from PHP 5.3 through 8.4.
   ->withPhpSets(php84: TRUE)
-  // Behat attribute sets - converts annotations to PHP 8 attributes.
-  ->withAttributesSets(behat: TRUE)
   // Code quality improvement sets.
   ->withPreparedSets(
     codeQuality: TRUE,
@@ -107,7 +104,6 @@
   // Additional rules.
   ->withRules([
     DeclareStrictTypesRector::class,
-    YieldDataProviderRector::class,
   ])
   // Configure Drupal autoloading.
   ->withAutoloadPaths((function (): array {
