@@ -77,22 +77,6 @@
       $this->moduleInstaller->uninstall(['toolbar']);
     }
 
-    $this->moduleInstaller->install([
-      'coffee',
-      'config_split',
-      'config_update',
-      'media',
-      'environment_indicator',
-      'navigation_extra_tools',
-      'pathauto',
-      'redirect',
-      'reroute_email',
-      'robotstxt',
-      'shield',
-      'stage_file_proxy',
-      'xmlsitemap',
-    ]);
-
     $this->moduleInstaller->install(['redis']);
 
     $this->moduleInstaller->install(['clamav']);
@@ -100,23 +84,10 @@
 
     $this->moduleInstaller->install(['search_api', 'search_api_solr']);
 
-    $this->moduleInstaller->install(['sdc_devel']);
-
-    $this->moduleInstaller->install(['devel']);
-
     $this->moduleInstaller->install(['sw_search']);
 
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
