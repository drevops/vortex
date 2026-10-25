@@ -97,15 +97,11 @@
     $this->moduleInstaller->install(['clamav']);
     $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
 
-    $this->moduleInstaller->install(['search_api', 'search_api_solr']);
-
     $this->moduleInstaller->install(['sdc_devel']);
 
     $this->moduleInstaller->install(['devel']);
 
     $this->moduleInstaller->install(['reroute_email']);
-
-    $this->moduleInstaller->install(['sw_search']);
 
     $this->moduleInstaller->install(['sw_demo']);
 
