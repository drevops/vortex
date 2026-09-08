@@ -80,8 +80,8 @@
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['reroute_email.settings']['address'] = 'webmaster@death-star.com';
+    $config['reroute_email.settings']['allowed'] = '*@death-star.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $this->assertConfig($config);
 
@@ -163,8 +163,8 @@
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['reroute_email.settings']['address'] = 'webmaster@death-star.com';
+    $config['reroute_email.settings']['allowed'] = '*@death-star.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
     $this->assertConfig($config);
 
@@ -221,8 +221,8 @@
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['reroute_email.settings']['address'] = 'webmaster@death-star.com';
+    $config['reroute_email.settings']['allowed'] = '*@death-star.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
@@ -279,8 +279,8 @@
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['reroute_email.settings']['address'] = 'webmaster@death-star.com';
+    $config['reroute_email.settings']['allowed'] = '*@death-star.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
@@ -380,8 +380,8 @@
     $config['system.mail']['interface']['default'] = 'test_mail_collector';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['reroute_email.settings']['address'] = 'webmaster@death-star.com';
+    $config['reroute_email.settings']['allowed'] = '*@death-star.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
