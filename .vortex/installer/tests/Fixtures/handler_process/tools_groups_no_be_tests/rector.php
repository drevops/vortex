@@ -35,7 +35,6 @@
 use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
 use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
 use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
-use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
 use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
 use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
 use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
@@ -83,8 +82,6 @@
   // PHP version upgrade sets. Called without an argument, the target version
   // comes from `composer.json`, so the sets follow the project's PHP version.
   ->withPhpSets()
-  // Behat attribute sets - converts annotations to PHP 8 attributes.
-  ->withAttributesSets(behat: TRUE)
   // Code quality improvement sets.
   ->withPreparedSets(
     codeQuality: TRUE,
@@ -103,7 +100,6 @@
   // Additional rules.
   ->withRules([
     DeclareStrictTypesRector::class,
-    YieldDataProviderRector::class,
   ])
   // Import configuration.
   ->withImportNames(importNames: FALSE, importDocBlockNames: FALSE);
