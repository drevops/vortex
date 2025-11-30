@@ -44,10 +44,10 @@
 
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
@@ -72,7 +72,7 @@
     RenameVariableToMatchNewTypeRector::class,
     SimplifyEmptyCheckOnEmptyArrayRector::class,
     StringClassNameToClassConstantRector::class => [
-      __DIR__ . '/web/sites/default/includes/**/*',
+      __DIR__ . '/docroot/sites/default/includes/**/*',
     ],
     // Directories to skip.
     '*/vendor/*',
