@@ -75,7 +75,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -157,7 +156,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -212,7 +210,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -269,7 +266,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -328,7 +324,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
