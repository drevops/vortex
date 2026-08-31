@@ -44,7 +44,6 @@
 return RectorConfig::configure()
   ->withPaths([
     __DIR__ . '/web/modules/custom',
-    __DIR__ . '/web/themes/custom',
     __DIR__ . '/web/sites/default/settings.php',
     __DIR__ . '/web/sites/default/includes',
     __DIR__ . '/tests',
@@ -65,7 +64,6 @@
     RemoveAlwaysTrueIfConditionRector::class,
     RemoveUnusedPublicMethodParameterRector::class => [
       __DIR__ . '/web/modules/custom/*/src/Hook/*',
-      __DIR__ . '/web/themes/custom/*/src/Hook/*',
     ],
     RenameForeachValueVariableToMatchExprVariableRector::class,
     RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
