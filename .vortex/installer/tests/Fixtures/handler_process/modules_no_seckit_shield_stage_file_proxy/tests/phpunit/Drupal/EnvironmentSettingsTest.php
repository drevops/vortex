@@ -76,7 +76,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['reroute_email.settings']['enable'] = TRUE;
@@ -159,7 +158,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['reroute_email.settings']['enable'] = TRUE;
@@ -215,7 +213,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -223,8 +220,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -273,7 +268,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -281,8 +275,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -373,7 +365,6 @@
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -382,8 +373,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
