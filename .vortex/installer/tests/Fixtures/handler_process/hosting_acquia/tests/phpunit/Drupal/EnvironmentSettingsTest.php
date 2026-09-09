@@ -24,6 +24,22 @@
 class EnvironmentSettingsTest extends SettingsTestCase {
 
   /**
+   * Path to the Acquia settings file fixture.
+   */
+  protected ?string $acquiaSettingsFixture = NULL;
+
+  /**
+   * {@inheritdoc}
+   */
+  protected function tearDown(): void {
+    if (!is_null($this->acquiaSettingsFixture) && file_exists($this->acquiaSettingsFixture)) {
+      unlink($this->acquiaSettingsFixture);
+    }
+
+    parent::tearDown();
+  }
+
+  /**
    * Test the detection of the resulting environment type.
    */
   #[DataProvider('dataProviderEnvironmentTypeDetection')]
@@ -58,6 +74,50 @@
       self::ENVIRONMENT_LOCAL,
     ];
 
+    // Acquia.
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => TRUE,
+      ],
+      self::ENVIRONMENT_DEV,
+    ];
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => 'prod',
+      ],
+      self::ENVIRONMENT_PROD,
+    ];
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => 'stage',
+      ],
+      self::ENVIRONMENT_STAGE,
+    ];
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => 'test',
+      ],
+      self::ENVIRONMENT_STAGE,
+    ];
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => 'dev',
+      ],
+      self::ENVIRONMENT_DEV,
+    ];
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => 'ode1',
+      ],
+      self::ENVIRONMENT_DEV,
+    ];
+    yield [
+      [
+        'AH_SITE_ENVIRONMENT' => 'nonode1',
+      ],
+      self::ENVIRONMENT_DEV,
+    ];
+
   }
 
   /**
@@ -310,6 +370,299 @@
     $settings['skip_permissions_hardening'] = TRUE;
 
     $this->assertSettings($settings);
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
+    $config['reroute_email.settings']['enable'] = TRUE;
+    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
+    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
+    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
+    $settings['auto_create_htaccess'] = TRUE;
+
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
+    $config['reroute_email.settings']['enable'] = TRUE;
+    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
+    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
+    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
+    $settings['auto_create_htaccess'] = TRUE;
+
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
+    $config['reroute_email.settings']['enable'] = TRUE;
+    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
+    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
+    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings = $this->expectedSettings(self::ENVIRONMENT_STAGE);
+    $settings['auto_create_htaccess'] = TRUE;
+
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
+    $config['reroute_email.settings']['enable'] = FALSE;
+    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
+    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $config['system.performance']['css']['preprocess'] = TRUE;
+    $config['system.performance']['js']['preprocess'] = TRUE;
+    $this->assertConfig($config);
+
+    $settings = $this->expectedSettings(self::ENVIRONMENT_PROD);
+    $settings['auto_create_htaccess'] = TRUE;
+
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test Acquia config_sync_directory override with DRUPAL_CONFIG_PATH.
+   */
+  public function testEnvironmentAcquiaConfigPathOverride(): void {
+    $this->setEnvVars([
+      'AH_SITE_ENVIRONMENT' => 1,
+      'DRUPAL_CONFIG_PATH' => 'custom_acquia_config',
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
+    $config['reroute_email.settings']['enable'] = TRUE;
+    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
+    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
+    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_sync_directory'] = 'custom_acquia_config';
+
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test Acquia config_sync_directory fallback to config_vcs_directory.
+   *
+   * When DRUPAL_CONFIG_PATH is not set but config_vcs_directory is provided
+   * by the Acquia-included settings file, config_sync_directory should fall
+   * back to config_vcs_directory.
+   */
+  public function testEnvironmentAcquiaConfigVcsDirectoryFallback(): void {
+    $this->setEnvVars([
+      'AH_SITE_ENVIRONMENT' => 1,
+    ]);
+
+    // Pre-seed config_vcs_directory to simulate the value set by the
+    // Acquia-included settings file from /var/www/site-php/.
+    $this->requireSettingsFile([
+      'config_vcs_directory' => '/var/www/site-php/mysite/config',
+    ]);
+
+    $config['acquia_hosting_settings_autoconnect'] = FALSE;
+    $config['config_split.config_split.dev']['status'] = TRUE;
+    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
+    $config['environment_indicator.indicator']['fg_color'] = '#000000';
+    $config['environment_indicator.indicator']['name'] = self::ENVIRONMENT_DEV;
+    $config['environment_indicator.settings']['favicon'] = TRUE;
+    $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
+    $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
+    $config['reroute_email.settings']['enable'] = TRUE;
+    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
+    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
+    $config['shield.settings']['shield_enable'] = TRUE;
+    $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
+    $config['xmlsitemap_engines.settings']['submit'] = FALSE;
+    $config['system.performance']['cache']['page']['max_age'] = 900;
+    $this->assertConfig($config);
+
+    $settings = $this->expectedSettings(self::ENVIRONMENT_DEV);
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_sync_directory'] = '/var/www/site-php/mysite/config';
+    $settings['config_vcs_directory'] = '/var/www/site-php/mysite/config';
+
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test the temporary file path resolution on Acquia.
+   */
+  #[DataProvider('dataProviderEnvironmentAcquiaTempPath')]
+  public function testEnvironmentAcquiaTempPath(array $vars, string $expected_path): void {
+    $this->acquiaSettingsFixture = $this->createAcquiaSettingsFixture();
+
+    $this->setEnvVars($vars + ['DRUPAL_ACQUIA_SETTINGS_FILE' => $this->acquiaSettingsFixture]);
+
+    $this->requireSettingsFile();
+
+    $this->assertSettingsContains(['file_temp_path' => $expected_path]);
+  }
+
+  /**
+   * Data provider for testEnvironmentAcquiaTempPath().
+   */
+  public static function dataProviderEnvironmentAcquiaTempPath(): \Iterator {
+    yield 'default' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite'],
+      '/tmp',
+    ];
+
+    yield 'shared mount' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => '1'],
+      '/mnt/gfs/mysite.dev/tmp',
+    ];
+
+    yield 'shared mount without a site group' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'DRUPAL_TMP_PATH_IS_SHARED' => '1'],
+      '/tmp',
+    ];
+
+    yield 'shared mount variable set to an empty value' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => ''],
+      '/tmp',
+    ];
+
+    yield 'shared mount variable set to zero' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => '0'],
+      '/tmp',
+    ];
+
+    yield 'shared mount variable set to a non-numeric truthy value' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => 'true'],
+      '/tmp',
+    ];
+
+    yield 'explicit override' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH' => '/custom/tmp'],
+      '/custom/tmp',
+    ];
+
+    yield 'explicit override wins over the shared mount' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH_IS_SHARED' => '1', 'DRUPAL_TMP_PATH' => '/custom/tmp'],
+      '/custom/tmp',
+    ];
+
+    yield 'explicit override set to an empty value' => [
+      ['AH_SITE_ENVIRONMENT' => 'dev', 'AH_SITE_GROUP' => 'mysite', 'DRUPAL_TMP_PATH' => ''],
+      '/tmp',
+    ];
+  }
+
+  /**
+   * Create an Acquia settings file fixture.
+   *
+   * The settings file is required when a site group is set, so the shared
+   * mount branch can only be reached through a file that exists.
+   *
+   * @return string
+   *   Path to the settings file fixture.
+   */
+  protected function createAcquiaSettingsFixture(): string {
+    $dir = getcwd() . '/.artifacts/tmp';
+
+    if (!is_dir($dir)) {
+      mkdir($dir, 0777, TRUE);
+    }
+
+    $file = $dir . '/' . uniqid('acquia-settings-') . '.inc';
+    file_put_contents($file, "<?php\n");
+
+    return $file;
   }
 
   /**
