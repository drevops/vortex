@@ -205,7 +205,7 @@
    * Require settings file.
    */
   protected function requireSettingsFile(array $pre_settings = [], array $pre_config = []): void {
-    $app_root = getcwd() . '/web';
+    $app_root = getcwd() . '/docroot';
 
     if (!file_exists($app_root)) {
       throw new \RuntimeException('Could not determine application root.');
@@ -251,7 +251,7 @@
    *   Array of configs to pre-populate.
    */
   protected function requireModuleSettingsFile(string $module, string $contrib_path, array $pre_settings = [], array $pre_config = []): void {
-    $app_root = getcwd() . '/web';
+    $app_root = getcwd() . '/docroot';
 
     if (!file_exists($app_root)) {
       throw new \RuntimeException('Could not determine application root.');
