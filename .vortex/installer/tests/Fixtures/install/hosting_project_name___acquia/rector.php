@@ -36,10 +36,10 @@
 
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
@@ -56,7 +56,7 @@
     RenameVariableToMatchNewTypeRector::class,
     SimplifyEmptyCheckOnEmptyArrayRector::class,
     StringClassNameToClassConstantRector::class => [
-      __DIR__ . '/web/sites/default/includes/**/*',
+      __DIR__ . '/docroot/sites/default/includes/**/*',
     ],
     // Directories to skip.
     '*/vendor/*',
