@@ -31,7 +31,7 @@
     - # <reason>.
       identifier: missingType.iterableValue
       paths:
-        - web/modules/custom/<module>/src/<File>.php
+        - docroot/modules/custom/<module>/src/<File>.php
 ```
 
 To leave a file out of the analysis, add it to `excludePaths`. The baseline, `phpstan-baseline.neon`, is for onboarding an existing codebase, not for ignoring a new error.
@@ -44,7 +44,7 @@
 ->withSkip([
   // <reason>.
   SimplifyIfReturnBoolRector::class => [
-    __DIR__ . '/web/modules/custom/<module>/src/<File>.php',
+    __DIR__ . '/docroot/modules/custom/<module>/src/<File>.php',
   ],
 ])
 ```
