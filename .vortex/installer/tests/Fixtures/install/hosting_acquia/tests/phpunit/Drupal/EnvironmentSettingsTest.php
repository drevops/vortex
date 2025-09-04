@@ -59,6 +59,50 @@
         static::ENVIRONMENT_LOCAL,
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
+
     ];
   }
 
@@ -310,6 +354,173 @@
     $settings['skip_permissions_hardening'] = TRUE;
     $settings['config_sync_directory'] = '../config/default';
     $settings['suspend_mail_send'] = TRUE;
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+    ];
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
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_DEV;
+    $settings['file_public_path'] = 'sites/default/files';
+    $settings['file_private_path'] = 'sites/default/files/private';
+    $settings['file_temp_path'] = '/tmp';
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['config_sync_directory'] = '../config/default';
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+    ];
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
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_DEV;
+    $settings['file_public_path'] = 'sites/default/files';
+    $settings['file_private_path'] = 'sites/default/files/private';
+    $settings['file_temp_path'] = '/tmp';
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['config_sync_directory'] = '../config/default';
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+    ];
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
+    $config['config_split.config_split.stage']['status'] = TRUE;
+    $config['environment_indicator.indicator']['bg_color'] = '#fff176';
+    $config['environment_indicator.indicator']['fg_color'] = '#000000';
+    $config['environment_indicator.indicator']['name'] = static::ENVIRONMENT_STAGE;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_STAGE;
+    $settings['file_public_path'] = 'sites/default/files';
+    $settings['file_private_path'] = 'sites/default/files/private';
+    $settings['file_temp_path'] = '/tmp';
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['config_sync_directory'] = '../config/default';
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+    ];
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
+    $config['system.performance']['css']['preprocess'] = TRUE;
+    $config['system.performance']['js']['preprocess'] = TRUE;
+    $this->assertConfig($config);
+
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = static::ENVIRONMENT_PROD;
+    $settings['file_public_path'] = 'sites/default/files';
+    $settings['file_private_path'] = 'sites/default/files/private';
+    $settings['file_temp_path'] = '/tmp';
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['config_sync_directory'] = '../config/default';
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
     $settings['trusted_host_patterns'] = [
       '^localhost$',
     ];
