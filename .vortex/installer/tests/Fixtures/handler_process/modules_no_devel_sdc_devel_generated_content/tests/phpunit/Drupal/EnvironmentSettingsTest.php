@@ -87,8 +87,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -169,8 +167,6 @@
     // Verify settings overrides.
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
       'testmode',
     ];
     $settings['config_sync_directory'] = 'custom_config';
@@ -225,8 +221,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -281,8 +275,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -360,8 +352,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
