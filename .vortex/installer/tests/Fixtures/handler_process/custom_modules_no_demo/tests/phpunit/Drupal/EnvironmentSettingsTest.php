@@ -86,7 +86,7 @@
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
-    $settings['config_exclude_modules'] = ['generated_content', 'testmode'];
+    $settings['config_exclude_modules'] = [];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_SUT;
@@ -164,7 +164,7 @@
 
     // Verify settings overrides.
     $settings['auto_create_htaccess'] = FALSE;
-    $settings['config_exclude_modules'] = ['generated_content', 'testmode'];
+    $settings['config_exclude_modules'] = [];
     $settings['config_sync_directory'] = 'custom_config';
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
@@ -216,7 +216,7 @@
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
-    $settings['config_exclude_modules'] = ['generated_content', 'testmode'];
+    $settings['config_exclude_modules'] = [];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_LOCAL;
@@ -268,7 +268,7 @@
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
-    $settings['config_exclude_modules'] = ['generated_content', 'testmode'];
+    $settings['config_exclude_modules'] = [];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_LOCAL;
@@ -322,7 +322,7 @@
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
-    $settings['config_exclude_modules'] = ['generated_content', 'testmode'];
+    $settings['config_exclude_modules'] = [];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_CI;
