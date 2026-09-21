@@ -15,10 +15,6 @@
  * The main purpose of these tests is to ensure that the settings and configs
  * appear in every environment as expected.
  *
- * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
- * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
- * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.AfterLast
- * phpcs:disable Drupal.Classes.ClassDeclaration.CloseBraceAfterBody
  */
 #[Group('drupal_settings')]
 class EnvironmentSettingsTest extends SettingsTestCase {
@@ -91,6 +87,7 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
@@ -188,6 +185,7 @@
       'generated_content',
       'reroute_email',
       'sdc_devel',
+      'storybook',
       'testmode',
     ];
     $settings['config_sync_directory'] = 'custom_config';
@@ -258,9 +256,11 @@
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
@@ -329,9 +329,11 @@
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
@@ -443,9 +445,11 @@
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
