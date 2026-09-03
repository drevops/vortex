@@ -76,7 +76,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['reroute_email.settings']['enable'] = TRUE;
@@ -158,7 +157,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['reroute_email.settings']['enable'] = TRUE;
@@ -213,7 +211,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -221,8 +218,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -270,7 +265,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -278,8 +272,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -369,7 +361,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -378,8 +369,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
