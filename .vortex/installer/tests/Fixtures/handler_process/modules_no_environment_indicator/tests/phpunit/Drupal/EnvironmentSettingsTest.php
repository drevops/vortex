@@ -70,11 +70,6 @@
 
     $this->requireSettingsFile();
 
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
@@ -144,11 +139,6 @@
     $this->assertEquals($databases, $this->databases);
 
     // Verify key config overrides.
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
@@ -191,11 +181,6 @@
 
     $config['automated_cron.settings']['interval'] = 0;
     $config['config_split.config_split.local']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
@@ -240,11 +225,6 @@
 
     $config['automated_cron.settings']['interval'] = 0;
     $config['config_split.config_split.local']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
@@ -291,11 +271,6 @@
 
     $config['automated_cron.settings']['interval'] = 0;
     $config['config_split.config_split.ci']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
