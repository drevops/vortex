@@ -190,7 +190,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
@@ -239,7 +238,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
@@ -290,7 +288,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.ci']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
