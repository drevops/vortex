@@ -36,7 +36,6 @@
 use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
 use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
 use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
-use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
 use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
 use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
 use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
@@ -99,7 +98,6 @@
   // Additional rules.
   ->withRules([
     DeclareStrictTypesRector::class,
-    YieldDataProviderRector::class,
   ])
   // Configure Drupal autoloading.
   ->withAutoloadPaths((function (): array {
