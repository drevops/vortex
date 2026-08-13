@@ -47,7 +47,6 @@
 return RectorConfig::configure()
   ->withPaths([
     __DIR__ . '/web/modules/custom',
-    __DIR__ . '/web/themes/custom',
     __DIR__ . '/web/sites/default/settings.php',
     __DIR__ . '/web/sites/default/includes',
     __DIR__ . '/tests',
@@ -82,7 +81,6 @@
     // declares, including parameters the implementation does not use.
     RemoveUnusedPublicMethodParameterRector::class => [
       __DIR__ . '/web/modules/custom/*/src/Hook/*',
-      __DIR__ . '/web/themes/custom/*/src/Hook/*',
     ],
     // The settings includes name classes from modules that are registered
     // with the autoloader at runtime.
