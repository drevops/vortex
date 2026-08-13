@@ -46,10 +46,10 @@
 
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
@@ -81,13 +81,13 @@
     // Object-oriented hook implementations keep the signature the hook
     // declares, including parameters the implementation does not use.
     RemoveUnusedPublicMethodParameterRector::class => [
-      __DIR__ . '/web/modules/custom/*/src/Hook/*',
-      __DIR__ . '/web/themes/custom/*/src/Hook/*',
+      __DIR__ . '/docroot/modules/custom/*/src/Hook/*',
+      __DIR__ . '/docroot/themes/custom/*/src/Hook/*',
     ],
     // The settings includes name classes from modules that are registered
     // with the autoloader at runtime.
     StringClassNameToClassConstantRector::class => [
-      __DIR__ . '/web/sites/default/includes/*',
+      __DIR__ . '/docroot/sites/default/includes/*',
     ],
     // Rules that rewrite working code beyond a syntax upgrade.
     CompleteDynamicPropertiesRector::class,
