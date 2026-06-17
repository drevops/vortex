@@ -200,7 +200,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
@@ -254,7 +253,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.local']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_LOCAL;
@@ -310,7 +308,6 @@
     $this->requireSettingsFile();
 
     $config['automated_cron.settings']['interval'] = 0;
-    $config['config_split.config_split.ci']['status'] = TRUE;
     $config['environment_indicator.indicator']['bg_color'] = '#006600';
     $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
     $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_CI;
