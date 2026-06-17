@@ -89,7 +89,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -172,7 +171,6 @@
     // Verify settings overrides.
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'generated_content',
       'testmode',
     ];
     $settings['config_sync_directory'] = 'custom_config';
@@ -229,7 +227,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -286,7 +283,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -345,7 +341,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
