@@ -59,6 +59,186 @@
         self::ENVIRONMENT_LOCAL,
       ],
 
+      // Lagoon.
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'production',
+        ],
+        self::ENVIRONMENT_PROD,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_GIT_BRANCH' => 'main',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'main',
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+        ],
+        self::ENVIRONMENT_PROD,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_GIT_BRANCH' => 'main',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'master',
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_GIT_BRANCH' => 'master',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_GIT_BRANCH' => 'master',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+          'LAGOON_ENVIRONMENT_TYPE' => 'production',
+        ],
+        self::ENVIRONMENT_PROD,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_GIT_BRANCH' => 'main',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_GIT_BRANCH' => 'main',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+          'LAGOON_ENVIRONMENT_TYPE' => 'production',
+        ],
+        self::ENVIRONMENT_PROD,
+      ],
+
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'release',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'release/1.2.3',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'hotfix',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'hotfix/1.2.3',
+        ],
+        self::ENVIRONMENT_STAGE,
+      ],
+
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => FALSE,
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => FALSE,
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'somebranch',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => FALSE,
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => FALSE,
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'somebranch',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'otherbranch',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => '',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => '',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+          'LAGOON_GIT_BRANCH' => 'mainbranch',
+          'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'mainbranch',
+        ],
+        self::ENVIRONMENT_PROD,
+      ],
+      [
+        [
+          'LAGOON_KUBERNETES' => 1,
+          'LAGOON_ENVIRONMENT_TYPE' => 'development',
+        ],
+        self::ENVIRONMENT_DEV,
+      ],
     ];
   }
 
@@ -312,6 +492,214 @@
     $settings['suspend_mail_send'] = TRUE;
     $settings['trusted_host_patterns'] = [
       '^localhost$',
+    ];
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for preview environment.
+   */
+  public function testEnvironmentLagoonPreview(): void {
+    $this->setEnvVars([
+      'LAGOON_KUBERNETES' => 1,
+      'LAGOON_ENVIRONMENT_TYPE' => 'development',
+      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
+      'LAGOON_PROJECT' => 'test_project',
+      'LAGOON_GIT_BRANCH' => 'test_branch',
+      'LAGOON_GIT_SAFE_BRANCH' => 'test_branch',
+    ]);
+
+    $this->requireSettingsFile();
+
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
+    $settings['cache_prefix']['default'] = 'test_project_test_branch';
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = '../config/default';
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
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['reverse_proxy'] = TRUE;
+    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+      '^nginx\-php$',
+      '^.+\.au\.amazee\.io$',
+      '^example1\.com|example2/com$',
+    ];
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for Dev environment.
+   */
+  public function testEnvironmentLagoonDev(): void {
+    $this->setEnvVars([
+      'LAGOON_KUBERNETES' => 1,
+      'LAGOON_ENVIRONMENT_TYPE' => 'development',
+      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
+      'LAGOON_PROJECT' => 'test_project',
+      'LAGOON_GIT_BRANCH' => 'develop',
+      'LAGOON_GIT_SAFE_BRANCH' => 'develop',
+    ]);
+
+    $this->requireSettingsFile();
+
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
+    $settings['cache_prefix']['default'] = 'test_project_develop';
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = '../config/default';
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
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['reverse_proxy'] = TRUE;
+    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+      '^nginx\-php$',
+      '^.+\.au\.amazee\.io$',
+      '^example1\.com|example2/com$',
+    ];
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for Test environment.
+   */
+  public function testEnvironmentLagoonTest(): void {
+    $this->setEnvVars([
+      'LAGOON_KUBERNETES' => 1,
+      'LAGOON_ENVIRONMENT_TYPE' => 'development',
+      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
+      'LAGOON_PROJECT' => 'test_project',
+      'LAGOON_GIT_BRANCH' => 'master',
+      'LAGOON_GIT_SAFE_BRANCH' => 'master',
+    ]);
+
+    $this->requireSettingsFile();
+
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
+    $settings['cache_prefix']['default'] = 'test_project_master';
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = '../config/default';
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
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['reverse_proxy'] = TRUE;
+    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+      '^nginx\-php$',
+      '^.+\.au\.amazee\.io$',
+      '^example1\.com|example2/com$',
+    ];
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test per-environment settings for Prod environment.
+   */
+  public function testEnvironmentLagoonProd(): void {
+    $this->setEnvVars([
+      'LAGOON_KUBERNETES' => 1,
+      'LAGOON_ENVIRONMENT_TYPE' => 'production',
+      'LAGOON_ROUTES' => 'http://example1.com,https://example2/com',
+      'LAGOON_PROJECT' => 'test_project',
+      'LAGOON_GIT_BRANCH' => 'production',
+      'LAGOON_GIT_SAFE_BRANCH' => 'production',
+      'VORTEX_LAGOON_PRODUCTION_BRANCH' => 'production',
+    ]);
+
+    $this->requireSettingsFile();
+
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
+    $settings['cache_prefix']['default'] = 'test_project_production';
+    $settings['config_exclude_modules'] = [];
+    $settings['config_sync_directory'] = '../config/default';
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
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['reverse_proxy'] = TRUE;
+    $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+      '^nginx\-php$',
+      '^.+\.au\.amazee\.io$',
+      '^example1\.com|example2/com$',
     ];
     $this->assertSettings($settings);
   }
