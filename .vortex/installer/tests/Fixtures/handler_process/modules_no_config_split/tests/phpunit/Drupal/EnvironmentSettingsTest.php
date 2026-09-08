@@ -231,7 +231,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
@@ -301,7 +300,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
@@ -413,7 +411,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.ci']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
