@@ -59,6 +59,50 @@
         self::ENVIRONMENT_LOCAL,
       ],
 
+      // Acquia.
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => TRUE,
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'prod',
+        ],
+        self::ENVIRONMENT_PROD,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'stage',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'test',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'dev',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'ode1',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'AH_SITE_ENVIRONMENT' => 'nonode1',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+
     ];
   }
 
@@ -315,6 +359,177 @@
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
+    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_DEV;
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
+    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_DEV;
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
+    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_STAGE;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_STAGE;
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
+    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_PROD;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $config['system.performance']['css']['preprocess'] = TRUE;
+    $config['system.performance']['js']['preprocess'] = TRUE;
+    $this->assertConfig($config);
+
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_PROD;
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
