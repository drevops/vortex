@@ -201,7 +201,7 @@
    * Require settings file.
    */
   protected function requireSettingsFile(): void {
-    $app_root = getcwd() . '/web';
+    $app_root = getcwd() . '/docroot';
 
     if (!file_exists($app_root)) {
       throw new \RuntimeException('Could not determine application root.');
