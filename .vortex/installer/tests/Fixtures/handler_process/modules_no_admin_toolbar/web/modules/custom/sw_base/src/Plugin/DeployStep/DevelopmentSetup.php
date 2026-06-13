@@ -61,7 +61,6 @@
     $this->configFactory->getEditable('system.site')->set('name', 'star wars')->save();
 
     $this->moduleInstaller->install([
-      'admin_toolbar',
       'coffee',
       'config_split',
       'config_update',
