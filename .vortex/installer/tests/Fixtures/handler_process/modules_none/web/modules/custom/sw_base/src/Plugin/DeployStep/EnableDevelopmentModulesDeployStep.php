@@ -60,22 +60,6 @@
   public function run(): void {
     $this->configFactory->getEditable('system.site')->set('name', 'star wars')->save();
 
-    $this->moduleInstaller->install([
-      'admin_toolbar',
-      'coffee',
-      'config_split',
-      'config_update',
-      'media',
-      'environment_indicator',
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
@@ -82,10 +66,6 @@
     $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
 
     $this->moduleInstaller->install(['search_api', 'search_api_solr']);
-
-    $this->moduleInstaller->install(['sdc_devel']);
-
-    $this->moduleInstaller->install(['devel']);
 
     $this->moduleInstaller->install(['sw_search']);
 
