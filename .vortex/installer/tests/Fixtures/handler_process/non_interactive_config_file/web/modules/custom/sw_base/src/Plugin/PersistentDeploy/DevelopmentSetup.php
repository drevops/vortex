@@ -89,8 +89,6 @@
       'xmlsitemap',
     ]);
 
-    $this->moduleInstaller->install(['redis']);
-
     $this->moduleInstaller->install(['clamav']);
     $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
 
