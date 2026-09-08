@@ -79,9 +79,6 @@
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
-    $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $this->assertConfig($config);
 
@@ -89,7 +86,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -174,9 +170,6 @@
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
-    $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
     $this->assertConfig($config);
 
@@ -185,7 +178,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -243,9 +235,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
@@ -254,7 +243,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -313,9 +301,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
@@ -324,7 +309,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -426,9 +410,6 @@
     $config['system.logging']['error_level'] = 'all';
     $config['system.mail']['interface']['default'] = 'test_mail_collector';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
@@ -437,7 +418,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
