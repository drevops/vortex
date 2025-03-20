@@ -18,62 +18,6 @@
 class SwitchableSettingsTest extends SettingsTestCase {
 
   /**
-   * Test ClamAV configs in Daemon mode with defaults.
-   */
-  public function testClamavDaemonCustom(): void {
-    $this->setEnvVars([
-      'DRUPAL_CLAMAV_ENABLED' => TRUE,
-      'DRUPAL_CLAMAV_MODE' => 'daemon',
-      'CLAMAV_HOST' => 'custom_clamav_host',
-      'CLAMAV_PORT' => 3333,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $config['clamav.settings']['scan_mode'] = 0;
-    $config['clamav.settings']['mode_daemon_tcpip']['hostname'] = 'custom_clamav_host';
-    $config['clamav.settings']['mode_daemon_tcpip']['port'] = 3333;
-
-    $this->assertConfigContains($config);
-  }
-
-  /**
-   * Test ClamAV configs in Executable mode.
-   */
-  public function testClamavExecutable(): void {
-    $this->setEnvVars([
-      'DRUPAL_CLAMAV_ENABLED' => TRUE,
-      'CLAMAV_HOST' => 'custom_clamav_host',
-      'CLAMAV_PORT' => 3333,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $config['clamav.settings']['scan_mode'] = 1;
-    $config['clamav.settings']['executable_path'] = '/usr/bin/clamscan';
-
-    $this->assertConfigContains($config);
-  }
-
-  /**
-   * Test ClamAV configs in Daemon mode with defaults.
-   */
-  public function testClamavDaemonDefaults(): void {
-    $this->setEnvVars([
-      'DRUPAL_CLAMAV_ENABLED' => TRUE,
-      'DRUPAL_CLAMAV_MODE' => 'daemon',
-    ]);
-
-    $this->requireSettingsFile();
-
-    $config['clamav.settings']['scan_mode'] = 0;
-    $config['clamav.settings']['mode_daemon_tcpip']['hostname'] = 'clamav';
-    $config['clamav.settings']['mode_daemon_tcpip']['port'] = 3310;
-
-    $this->assertConfigContains($config);
-  }
-
-  /**
    * Test Config Split config.
    *
    * @dataProvider dataProviderConfigSplit
@@ -225,54 +169,6 @@
         ],
       ],
     ];
-  }
-
-  /**
-   * Test Redis settings.
-   */
-  public function testRedis(): void {
-    $this->setEnvVars([
-      'DRUPAL_REDIS_ENABLED' => 1,
-      'REDIS_HOST' => 'redis_host',
-      'REDIS_SERVICE_PORT' => 1234,
-      'VORTEX_REDIS_EXTENSION_LOADED' => 1,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $settings['redis.connection']['interface'] = 'PhpRedis';
-    $settings['redis.connection']['host'] = 'redis_host';
-    $settings['redis.connection']['port'] = 1234;
-    $settings['cache']['default'] = 'cache.backend.redis';
-
-    $this->assertArrayHasKey('bootstrap_container_definition', $this->settings);
-    unset($this->settings['bootstrap_container_definition']);
-
-    $this->assertSettingsContains($settings);
-  }
-
-  /**
-   * Test Redis partial settings.
-   */
-  public function testRedisPartial(): void {
-    $this->setEnvVars([
-      'DRUPAL_REDIS_ENABLED' => 1,
-      'REDIS_HOST' => 'redis_host',
-      'REDIS_SERVICE_PORT' => 1234,
-      'VORTEX_REDIS_EXTENSION_LOADED' => 0,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $settings['redis.connection']['interface'] = 'PhpRedis';
-    $settings['redis.connection']['host'] = 'redis_host';
-    $settings['redis.connection']['port'] = 1234;
-    $no_settings['cache']['default'] = 'cache.backend.redis';
-
-    $this->assertArrayNotHasKey('bootstrap_container_definition', $this->settings);
-
-    $this->assertSettingsContains($settings);
-    $this->assertSettingsNotContains($no_settings);
   }
 
   /**
