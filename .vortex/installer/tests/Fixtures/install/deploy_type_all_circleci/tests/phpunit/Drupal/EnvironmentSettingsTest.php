@@ -265,9 +265,9 @@
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
