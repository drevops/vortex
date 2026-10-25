@@ -84,7 +84,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -183,7 +182,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -255,7 +253,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -329,7 +326,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
@@ -404,7 +400,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
