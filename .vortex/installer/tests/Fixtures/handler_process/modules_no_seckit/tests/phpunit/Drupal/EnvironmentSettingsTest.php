@@ -217,8 +217,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -272,8 +270,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -329,8 +325,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
