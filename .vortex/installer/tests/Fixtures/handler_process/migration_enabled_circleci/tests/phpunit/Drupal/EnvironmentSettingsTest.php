@@ -143,6 +143,13 @@
     $databases['default']['default']['collation'] = 'utf8_general_ci';
     $databases['default']['default']['driver'] = 'mysql';
     $databases['default']['default']['prefix'] = '';
+    $databases['migrate']['default']['database'] = 'drupal';
+    $databases['migrate']['default']['username'] = 'drupal';
+    $databases['migrate']['default']['password'] = 'drupal';
+    $databases['migrate']['default']['host'] = 'localhost';
+    $databases['migrate']['default']['port'] = '';
+    $databases['migrate']['default']['prefix'] = '';
+    $databases['migrate']['default']['driver'] = 'mysql';
     $this->assertEquals($databases, $this->databases);
 
     // Verify key config overrides.
@@ -282,9 +289,9 @@
   }
 
   /**
-   * Test per-environment settings for GitHub Actions.
+   * Test per-environment settings for CircleCI.
    */
-  public function testEnvironmentGha(): void {
+  public function testEnvironmentCircleCi(): void {
     $this->setEnvVars([
       'CI' => TRUE,
     ]);
