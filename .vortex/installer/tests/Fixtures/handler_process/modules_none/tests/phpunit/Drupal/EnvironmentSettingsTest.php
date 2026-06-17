@@ -70,18 +70,7 @@
 
     $this->requireSettingsFile();
 
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = TRUE;
-    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
-    $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
@@ -149,18 +138,7 @@
     $this->assertEquals($databases, $this->databases);
 
     // Verify key config overrides.
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = TRUE;
-    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
-    $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
@@ -200,23 +178,9 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
-    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
@@ -254,23 +218,9 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
-    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
@@ -310,23 +260,9 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.ci']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
-    $config['shield.settings']['shield_enable'] = FALSE;
-    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
