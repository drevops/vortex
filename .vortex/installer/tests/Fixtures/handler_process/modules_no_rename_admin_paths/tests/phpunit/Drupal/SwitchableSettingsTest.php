@@ -449,35 +449,6 @@
   }
 
   /**
-   * Test Rename Admin Paths config.
-   */
-  #[DataProvider('dataProviderRenameAdminPaths')]
-  public function testRenameAdminPaths(array $vars, ?string $expected_path): void {
-    $this->setEnvVars($vars);
-
-    $this->requireSettingsFile();
-
-    if ($expected_path === NULL) {
-      $this->assertArrayNotHasKey('rename_admin_paths.settings', $this->config);
-
-      return;
-    }
-
-    $this->assertConfigContains(['rename_admin_paths.settings' => ['user_path' => TRUE, 'user_path_value' => $expected_path]]);
-  }
-
-  /**
-   * Data provider for testRenameAdminPaths().
-   */
-  public static function dataProviderRenameAdminPaths(): \Iterator {
-    yield 'variable not set' => [[], NULL];
-    yield 'variable set to an empty value' => [['DRUPAL_USER_PATH' => ''], NULL];
-    yield 'variable set to slashes only' => [['DRUPAL_USER_PATH' => '//'], NULL];
-    yield 'variable set' => [['DRUPAL_USER_PATH' => 'staff'], 'staff'];
-    yield 'variable set with surrounding slashes' => [['DRUPAL_USER_PATH' => '/staff/'], 'staff'];
-  }
-
-  /**
    * Test Shield config.
    */
   #[DataProvider('dataProviderShield')]
