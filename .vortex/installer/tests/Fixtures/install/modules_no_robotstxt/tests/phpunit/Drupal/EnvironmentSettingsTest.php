@@ -77,7 +77,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $this->assertConfig($config);
@@ -148,7 +147,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_SUT;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['system.performance']['cache']['page']['max_age'] = 1800;
     $this->assertConfig($config);
@@ -192,7 +190,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
@@ -238,7 +235,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
@@ -286,7 +282,6 @@
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
     $config['environment_indicator.settings']['favicon'] = TRUE;
     $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
-    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
     $config['shield.settings']['shield_enable'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
