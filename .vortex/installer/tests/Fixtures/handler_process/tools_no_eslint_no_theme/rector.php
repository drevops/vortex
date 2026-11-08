@@ -36,7 +36,6 @@
 return RectorConfig::configure()
   ->withPaths([
     __DIR__ . '/web/modules/custom',
-    __DIR__ . '/web/themes/custom',
     __DIR__ . '/web/sites/default/settings.php',
     __DIR__ . '/web/sites/default/includes',
     __DIR__ . '/tests',
@@ -56,7 +55,6 @@
     RemoveAlwaysTrueIfConditionRector::class,
     RemoveUnusedPublicMethodParameterRector::class => [
       __DIR__ . '/web/modules/custom/*/src/Hook/*',
-      __DIR__ . '/web/themes/custom/*/src/Hook/*',
     ],
     SimplifyEmptyCheckOnEmptyArrayRector::class,
     StringClassNameToClassConstantRector::class => [
