@@ -50,6 +50,49 @@
         static::ENVIRONMENT_CI,
       ],
 
+      // Acquia.
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => TRUE,
+        ],
+        static::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'prod',
+        ],
+        static::ENVIRONMENT_PROD,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'stage',
+        ],
+        static::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'test',
+        ],
+        static::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'dev',
+        ],
+        static::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'ode1',
+        ],
+        static::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'nonode1',
+        ],
+        static::ENVIRONMENT_DEV,
+      ],
     ];
   }
 
@@ -180,6 +223,164 @@
       '^.+\.docker\.amazee\.io$',
       '^nginx$',
     ];
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for dynamic environment.
+   */
+  public function testEnvironmentAcquiaDynamic(): void {
+    $this->setEnvVars([
+      'AH_SITE_ENVIRONMENT' => 1,
+    ]);
+
+    $this->requireSettingsFile();
+
+    $config['acquia_hosting_settings_autoconnect'] = FALSE;
+    $config['config_split.config_split.dev']['status'] = TRUE;
+    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
+    $config['environment_indicator.indicator']['fg_color'] = '#000000';
+    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_DEV;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $config['system.performance']['css']['preprocess'] = 1;
+    $config['system.performance']['js']['preprocess'] = 1;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_DEV;
+    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['trusted_host_patterns'][] = '^.+\.docker\.amazee\.io$';
+    $settings['trusted_host_patterns'][] = '^nginx$';
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for Dev environment.
+   */
+  public function testEnvironmentAcquiaDev(): void {
+    $this->setEnvVars([
+      'AH_SITE_ENVIRONMENT' => 1,
+    ]);
+
+    $this->requireSettingsFile();
+
+    $config['acquia_hosting_settings_autoconnect'] = FALSE;
+    $config['config_split.config_split.dev']['status'] = TRUE;
+    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
+    $config['environment_indicator.indicator']['fg_color'] = '#000000';
+    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_DEV;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $config['system.performance']['css']['preprocess'] = 1;
+    $config['system.performance']['js']['preprocess'] = 1;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_DEV;
+    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['trusted_host_patterns'][] = '^.+\.docker\.amazee\.io$';
+    $settings['trusted_host_patterns'][] = '^nginx$';
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for Test environment.
+   */
+  public function testEnvironmentAcquiaStage(): void {
+    $this->setEnvVars([
+      'AH_SITE_ENVIRONMENT' => 'stage',
+    ]);
+
+    $this->requireSettingsFile();
+
+    $config['acquia_hosting_settings_autoconnect'] = FALSE;
+    $config['config_split.config_split.test']['status'] = TRUE;
+    $config['environment_indicator.indicator']['bg_color'] = '#fff176';
+    $config['environment_indicator.indicator']['fg_color'] = '#000000';
+    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_STAGE;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $config['system.performance']['css']['preprocess'] = 1;
+    $config['system.performance']['js']['preprocess'] = 1;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_STAGE;
+    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['trusted_host_patterns'][] = '^.+\.docker\.amazee\.io$';
+    $settings['trusted_host_patterns'][] = '^nginx$';
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for Prod environment.
+   */
+  public function testEnvironmentAcquiaProd(): void {
+    $this->setEnvVars([
+      'AH_SITE_ENVIRONMENT' => 'prod',
+    ]);
+
+    $this->requireSettingsFile();
+
+    $config['acquia_hosting_settings_autoconnect'] = FALSE;
+    $config['environment_indicator.indicator']['bg_color'] = '#ef5350';
+    $config['environment_indicator.indicator']['fg_color'] = '#000000';
+    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_PROD;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $config['system.performance']['css']['preprocess'] = 1;
+    $config['system.performance']['js']['preprocess'] = 1;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = static::CONFIG_PATH_TESTING;
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_PROD;
+    $settings['file_private_path'] = static::PRIVATE_PATH_TESTING;
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['file_temp_path'] = static::TMP_PATH_TESTING;
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['trusted_host_patterns'][] = '^.+\.docker\.amazee\.io$';
+    $settings['trusted_host_patterns'][] = '^nginx$';
     $this->assertSettings($settings);
   }
 
