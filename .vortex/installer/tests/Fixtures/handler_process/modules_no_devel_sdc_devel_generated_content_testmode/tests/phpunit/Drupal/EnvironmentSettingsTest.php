@@ -87,9 +87,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
-      'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
@@ -169,9 +166,6 @@
     // Verify settings overrides.
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
-      'testmode',
     ];
     $settings['config_sync_directory'] = 'custom_config';
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -225,9 +219,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
-      'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
@@ -281,9 +272,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
-      'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
@@ -339,9 +327,6 @@
 
     $settings['auto_create_htaccess'] = FALSE;
     $settings['config_exclude_modules'] = [
-      'devel',
-      'generated_content',
-      'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
