@@ -36,7 +36,6 @@
 use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
 use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
 use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
-use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
 use Rector\Privatization\Rector\ClassConst\PrivatizeFinalClassConstantRector;
 use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
 use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
@@ -86,8 +85,6 @@
   // PHP version upgrade sets. Called without an argument, the target version
   // comes from `composer.json`, so the sets follow the project's PHP version.
   ->withPhpSets()
-  // Behat attribute sets - converts annotations to PHP 8 attributes.
-  ->withAttributesSets(behat: TRUE)
   // Code quality improvement sets.
   ->withPreparedSets(
     codeQuality: TRUE,
@@ -106,7 +103,6 @@
   // Additional rules.
   ->withRules([
     DeclareStrictTypesRector::class,
-    YieldDataProviderRector::class,
   ])
   // Import configuration.
   ->withImportNames(importNames: FALSE, importDocBlockNames: FALSE);
