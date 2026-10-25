@@ -102,6 +102,7 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -202,6 +203,7 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['config_sync_directory'] = 'custom_config';
@@ -276,9 +278,11 @@
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
     $settings['fast404_allow_anon_imagecache'] = TRUE;
@@ -350,9 +354,11 @@
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
     $settings['fast404_allow_anon_imagecache'] = TRUE;
@@ -388,9 +394,9 @@
   }
 
   /**
-   * Test per-environment settings for GitHub Actions.
+   * Test per-environment settings for CircleCI.
    */
-  public function testEnvironmentGha(): void {
+  public function testEnvironmentCircleCi(): void {
     $this->setEnvVars([
       'CI' => TRUE,
     ]);
@@ -426,9 +432,11 @@
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
     $settings['fast404_allow_anon_imagecache'] = TRUE;
