@@ -196,8 +196,6 @@
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['config_exclude_modules'] = [];
@@ -242,8 +240,6 @@
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['config_exclude_modules'] = [];
@@ -290,8 +286,6 @@
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['config_exclude_modules'] = [];
