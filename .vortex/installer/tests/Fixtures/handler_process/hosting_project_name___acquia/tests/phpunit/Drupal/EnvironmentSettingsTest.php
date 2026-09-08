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
+    if (!is_null($this->acquiaSettingsFixture)) {
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
@@ -476,6 +536,480 @@
     ];
 
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
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [
+      'devel',
+      'generated_content',
+      'reroute_email',
+      'sdc_devel',
+      'testmode',
+    ];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_DEV;
+    $settings['fast404_allow_anon_imagecache'] = FALSE;
+    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
+    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
+    $settings['fast404_path_check'] = FALSE;
+    $settings['fast404_respect_redirect'] = FALSE;
+    $settings['fast404_url_whitelisting'] = TRUE;
+    $settings['fast404_whitelist'] = [
+      'index.php',
+      'rss.xml',
+      'cron.php',
+      'xmlrpc.php',
+    ];
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
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [
+      'devel',
+      'generated_content',
+      'reroute_email',
+      'sdc_devel',
+      'testmode',
+    ];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_DEV;
+    $settings['fast404_allow_anon_imagecache'] = FALSE;
+    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
+    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
+    $settings['fast404_path_check'] = FALSE;
+    $settings['fast404_respect_redirect'] = FALSE;
+    $settings['fast404_url_whitelisting'] = TRUE;
+    $settings['fast404_whitelist'] = [
+      'index.php',
+      'rss.xml',
+      'cron.php',
+      'xmlrpc.php',
+    ];
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
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [
+      'devel',
+      'generated_content',
+      'reroute_email',
+      'sdc_devel',
+      'testmode',
+    ];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_STAGE;
+    $settings['fast404_allow_anon_imagecache'] = FALSE;
+    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
+    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
+    $settings['fast404_path_check'] = FALSE;
+    $settings['fast404_respect_redirect'] = FALSE;
+    $settings['fast404_url_whitelisting'] = TRUE;
+    $settings['fast404_whitelist'] = [
+      'index.php',
+      'rss.xml',
+      'cron.php',
+      'xmlrpc.php',
+    ];
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
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [
+      'devel',
+      'generated_content',
+      'reroute_email',
+      'sdc_devel',
+      'testmode',
+    ];
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_PROD;
+    $settings['fast404_allow_anon_imagecache'] = FALSE;
+    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
+    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
+    $settings['fast404_path_check'] = FALSE;
+    $settings['fast404_respect_redirect'] = FALSE;
+    $settings['fast404_url_whitelisting'] = TRUE;
+    $settings['fast404_whitelist'] = [
+      'index.php',
+      'rss.xml',
+      'cron.php',
+      'xmlrpc.php',
+    ];
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
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [
+      'devel',
+      'generated_content',
+      'reroute_email',
+      'sdc_devel',
+      'testmode',
+    ];
+    $settings['config_sync_directory'] = 'custom_acquia_config';
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_DEV;
+    $settings['fast404_allow_anon_imagecache'] = FALSE;
+    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
+    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
+    $settings['fast404_path_check'] = FALSE;
+    $settings['fast404_respect_redirect'] = FALSE;
+    $settings['fast404_url_whitelisting'] = TRUE;
+    $settings['fast404_whitelist'] = [
+      'index.php',
+      'rss.xml',
+      'cron.php',
+      'xmlrpc.php',
+    ];
+    $settings['file_public_path'] = 'sites/default/files';
+    $settings['file_private_path'] = 'sites/default/files/private';
+    $settings['file_temp_path'] = '/tmp';
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+    ];
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
+    $settings['auto_create_htaccess'] = TRUE;
+    $settings['config_exclude_modules'] = [
+      'devel',
+      'generated_content',
+      'reroute_email',
+      'sdc_devel',
+      'testmode',
+    ];
+    $settings['config_sync_directory'] = '/var/www/site-php/mysite/config';
+    $settings['config_vcs_directory'] = '/var/www/site-php/mysite/config';
+    $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
+    $settings['entity_update_batch_size'] = 50;
+    $settings['environment'] = self::ENVIRONMENT_DEV;
+    $settings['fast404_allow_anon_imagecache'] = FALSE;
+    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
+    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
+    $settings['fast404_path_check'] = FALSE;
+    $settings['fast404_respect_redirect'] = FALSE;
+    $settings['fast404_url_whitelisting'] = TRUE;
+    $settings['fast404_whitelist'] = [
+      'index.php',
+      'rss.xml',
+      'cron.php',
+      'xmlrpc.php',
+    ];
+    $settings['file_public_path'] = 'sites/default/files';
+    $settings['file_private_path'] = 'sites/default/files/private';
+    $settings['file_temp_path'] = '/tmp';
+    $settings['file_scan_ignore_directories'] = [
+      'node_modules',
+      'bower_components',
+    ];
+    $settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');
+    $settings['maintenance_theme'] = 'claro';
+    $settings['trusted_host_patterns'] = [
+      '^localhost$',
+    ];
+
+    $this->assertSettings($settings);
+  }
+
+  /**
+   * Test the temporary file path resolution on Acquia.
+   */
+  #[DataProvider('dataProviderEnvironmentAcquiaTempPath')]
+  public function testEnvironmentAcquiaTempPath(array $vars, string $expected_path): void {
+    $this->acquiaSettingsFixture = getcwd() . '/.artifacts/tmp/' . uniqid('acquia-settings-') . '.inc';
+    file_put_contents($this->acquiaSettingsFixture, "<?php\n");
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
   }
 
 }
