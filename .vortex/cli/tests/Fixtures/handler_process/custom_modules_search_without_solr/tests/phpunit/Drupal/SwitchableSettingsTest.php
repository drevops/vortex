@@ -297,35 +297,6 @@
   }
 
   /**
-   * Test Search API server settings with defaults.
-   */
-  public function testSearchApiDefaults(): void {
-    $this->requireSettingsFile();
-
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
-
-    $this->assertConfigContains($config);
-  }
-
-  /**
-   * Test Search API server settings with custom host and port.
-   */
-  public function testSearchApiCustom(): void {
-    $this->setEnvVars([
-      'SOLR_HOST' => 'custom_solr_host',
-      'SOLR_PORT' => 9999,
-    ]);
-
-    $this->requireSettingsFile();
-
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'custom_solr_host';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 9999;
-
-    $this->assertConfigContains($config);
-  }
-
-  /**
    * Test Shield config.
    */
   #[DataProvider('dataProviderShield')]
