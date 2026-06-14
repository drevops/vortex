@@ -78,9 +78,6 @@
 
     $this->moduleInstaller->install(['redis']);
 
-    $this->moduleInstaller->install(['clamav']);
-    $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
-
     $this->moduleInstaller->install(['search_api', 'search_api_solr']);
 
     $this->moduleInstaller->install(['sw_search']);
