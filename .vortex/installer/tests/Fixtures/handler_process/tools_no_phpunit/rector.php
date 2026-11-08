@@ -28,7 +28,6 @@
 use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
 use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
 use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
-use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
 use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
 use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
 use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
@@ -83,9 +82,5 @@
   // carry the autoload paths and the file extensions Drupal executes PHP
   // from, so this file declares neither.
   ->withComposerBased(drupal: TRUE)
-  // Additional rules.
-  ->withRules([
-    YieldDataProviderRector::class,
-  ])
   // Import configuration.
   ->withImportNames(importNames: FALSE, importDocBlockNames: FALSE);
