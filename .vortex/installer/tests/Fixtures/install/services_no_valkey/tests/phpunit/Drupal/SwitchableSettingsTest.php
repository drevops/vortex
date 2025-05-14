@@ -264,54 +264,6 @@
   }
 
   /**
-   * Test Valkey settings.
-   */
-  public function testValkey(): void {
-    $this->setEnvVars([
-      'DRUPAL_VALKEY_ENABLED' => 1,
-      'VALKEY_HOST' => 'valkey_host',
-      'VALKEY_SERVICE_PORT' => 1234,
-      'VORTEX_VALKEY_EXTENSION_LOADED' => 1,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $settings['redis.connection']['interface'] = 'PhpRedis';
-    $settings['redis.connection']['host'] = 'valkey_host';
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
-   * Test Valkey partial settings.
-   */
-  public function testValkeyPartial(): void {
-    $this->setEnvVars([
-      'DRUPAL_VALKEY_ENABLED' => 1,
-      'VALKEY_HOST' => 'valkey_host',
-      'VALKEY_SERVICE_PORT' => 1234,
-      'VORTEX_VALKEY_EXTENSION_LOADED' => 0,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $settings['redis.connection']['interface'] = 'PhpRedis';
-    $settings['redis.connection']['host'] = 'valkey_host';
-    $settings['redis.connection']['port'] = 1234;
-    $no_settings['cache']['default'] = 'cache.backend.redis';
-
-    $this->assertArrayNotHasKey('bootstrap_container_definition', $this->settings);
-
-    $this->assertSettingsContains($settings);
-    $this->assertSettingsNotContains($no_settings);
-  }
-
-  /**
    * Test Shield config.
    *
    * @dataProvider dataProviderShield
