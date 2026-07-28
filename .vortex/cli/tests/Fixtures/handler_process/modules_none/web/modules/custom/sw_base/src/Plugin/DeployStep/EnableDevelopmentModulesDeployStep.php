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
@@ -99,10 +83,6 @@
     $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
 
     $this->moduleInstaller->install(['search_api', 'search_api_solr']);
-
-    $this->moduleInstaller->install(['sdc_devel']);
-
-    $this->moduleInstaller->install(['devel']);
 
     $this->moduleInstaller->install(['sw_search']);
 
