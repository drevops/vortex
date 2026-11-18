@@ -35,10 +35,10 @@
 
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
     __DIR__ . '/drush/Listeners',
     __DIR__ . '/tests',
   ])
@@ -56,12 +56,12 @@
     PrivatizeLocalGetterToPropertyRector::class,
     RemoveAlwaysTrueIfConditionRector::class,
     RemoveUnusedPublicMethodParameterRector::class => [
-      __DIR__ . '/web/modules/custom/*/src/Hook/*',
-      __DIR__ . '/web/themes/custom/*/src/Hook/*',
+      __DIR__ . '/docroot/modules/custom/*/src/Hook/*',
+      __DIR__ . '/docroot/themes/custom/*/src/Hook/*',
     ],
     SimplifyEmptyCheckOnEmptyArrayRector::class,
     StringClassNameToClassConstantRector::class => [
-      __DIR__ . '/web/sites/default/includes/*',
+      __DIR__ . '/docroot/sites/default/includes/*',
     ],
     // Directories to skip.
     '*/vendor/*',
