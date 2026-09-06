@@ -45,10 +45,10 @@
 
 return RectorConfig::configure()
   ->withPaths([
-    __DIR__ . '/web/modules/custom',
-    __DIR__ . '/web/themes/custom',
-    __DIR__ . '/web/sites/default/settings.php',
-    __DIR__ . '/web/sites/default/includes',
+    __DIR__ . '/docroot/modules/custom',
+    __DIR__ . '/docroot/themes/custom',
+    __DIR__ . '/docroot/sites/default/settings.php',
+    __DIR__ . '/docroot/sites/default/includes',
     __DIR__ . '/tests',
   ])
   ->withSkip([
@@ -68,8 +68,8 @@
     PrivatizeLocalGetterToPropertyRector::class,
     RemoveAlwaysTrueIfConditionRector::class,
     RemoveUnusedPublicMethodParameterRector::class => [
-      __DIR__ . '/web/modules/custom/*/src/Hook/*',
-      __DIR__ . '/web/themes/custom/*/src/Hook/*',
+      __DIR__ . '/docroot/modules/custom/*/src/Hook/*',
+      __DIR__ . '/docroot/themes/custom/*/src/Hook/*',
     ],
     RenameForeachValueVariableToMatchExprVariableRector::class,
     RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
@@ -78,7 +78,7 @@
     RenameVariableToMatchNewTypeRector::class,
     SimplifyEmptyCheckOnEmptyArrayRector::class,
     StringClassNameToClassConstantRector::class => [
-      __DIR__ . '/web/sites/default/includes/*',
+      __DIR__ . '/docroot/sites/default/includes/*',
     ],
     // Directories to skip.
     '*/vendor/*',
