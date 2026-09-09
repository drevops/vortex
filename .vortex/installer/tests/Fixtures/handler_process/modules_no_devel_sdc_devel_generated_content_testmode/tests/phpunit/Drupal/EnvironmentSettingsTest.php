@@ -327,11 +327,7 @@
     return [
       'auto_create_htaccess' => FALSE,
       'config_exclude_modules' => [
-        'devel',
-        'generated_content',
         'reroute_email',
-        'sdc_devel',
-        'testmode',
       ],
       'config_sync_directory' => '../config/default',
       'container_yamls' => [$this->app_root . '/' . $this->site_path . '/services.yml'],
