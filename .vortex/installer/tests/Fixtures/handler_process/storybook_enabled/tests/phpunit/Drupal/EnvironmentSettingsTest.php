@@ -91,6 +91,7 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -188,6 +189,7 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['config_sync_directory'] = 'custom_config';
@@ -258,9 +260,11 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['container_yamls'][1] = $this->app_root . '/' . $this->site_path . '/includes/modules/services.storybook.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_LOCAL;
     $settings['fast404_allow_anon_imagecache'] = FALSE;
@@ -329,9 +333,11 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['container_yamls'][1] = $this->app_root . '/' . $this->site_path . '/includes/modules/services.storybook.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_LOCAL;
     $settings['fast404_allow_anon_imagecache'] = FALSE;
@@ -443,9 +449,11 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['container_yamls'][1] = $this->app_root . '/' . $this->site_path . '/includes/modules/services.storybook.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_CI;
     $settings['fast404_allow_anon_imagecache'] = FALSE;
