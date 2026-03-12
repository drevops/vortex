@@ -202,8 +202,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -251,8 +249,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -302,8 +298,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
