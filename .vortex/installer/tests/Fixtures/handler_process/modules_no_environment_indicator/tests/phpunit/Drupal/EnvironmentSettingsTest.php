@@ -72,11 +72,6 @@
 
     $this->requireSettingsFile();
 
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['system.performance']['cache']['page']['max_age'] = 900;
@@ -143,11 +138,6 @@
     $this->assertEquals($databases, $this->databases);
 
     // Verify key config overrides.
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['system.performance']['cache']['page']['max_age'] = 1800;
@@ -187,11 +177,6 @@
 
     $config['automated_cron.settings']['interval'] = 0;
     $config['config_split.config_split.local']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -233,11 +218,6 @@
 
     $config['automated_cron.settings']['interval'] = 0;
     $config['config_split.config_split.local']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
@@ -281,11 +261,6 @@
 
     $config['automated_cron.settings']['interval'] = 0;
     $config['config_split.config_split.ci']['status'] = TRUE;
-    $config['environment_indicator.indicator']['bg_color'] = '#006600';
-    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
-    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
-    $config['environment_indicator.settings']['favicon'] = TRUE;
-    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
     $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
