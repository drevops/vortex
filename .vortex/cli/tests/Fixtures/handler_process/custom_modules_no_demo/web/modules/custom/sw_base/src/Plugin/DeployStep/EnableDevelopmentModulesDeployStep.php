@@ -106,8 +106,6 @@
 
     $this->moduleInstaller->install(['sw_search']);
 
-    $this->moduleInstaller->install(['sw_demo']);
-
     // The module creates its content while it installs, and only when
     // GENERATED_CONTENT_CREATE is set, so it installs after the modules that
     // supply the generated content plugins.
