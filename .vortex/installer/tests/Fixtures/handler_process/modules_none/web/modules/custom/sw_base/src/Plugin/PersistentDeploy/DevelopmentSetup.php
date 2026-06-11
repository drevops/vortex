@@ -73,22 +73,6 @@
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
