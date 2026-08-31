@@ -108,15 +108,6 @@
 
     $this->moduleInstaller->install(['sw_demo']);
 
-    // The module creates its content while it installs, and only when
-    // GENERATED_CONTENT_CREATE is set, so it installs after the modules that
-    // supply the generated content plugins.
-    if (getenv('DRUPAL_GENERATED_CONTENT_SKIP') !== '1') {
-      putenv('GENERATED_CONTENT_CREATE=1');
-    }
-
-    $this->moduleInstaller->install(['generated_content']);
-    putenv('GENERATED_CONTENT_CREATE');
   }
 
 }
