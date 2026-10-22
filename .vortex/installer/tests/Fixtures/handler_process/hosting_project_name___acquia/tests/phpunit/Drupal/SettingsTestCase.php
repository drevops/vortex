@@ -188,7 +188,7 @@
    * Require settings file.
    */
   protected function requireSettingsFile(array $pre_settings = [], array $pre_config = []): void {
-    $app_root = getcwd() . '/web';
+    $app_root = getcwd() . '/docroot';
 
     if (!file_exists($app_root)) {
       throw new \RuntimeException('Could not determine application root.');
@@ -227,7 +227,7 @@
    *   Array of configs to pre-populate.
    */
   protected function requireModuleSettingsFile(string $module, string $contrib_path, array $pre_settings = [], array $pre_config = []): void {
-    $app_root = getcwd() . '/web';
+    $app_root = getcwd() . '/docroot';
 
     if (!file_exists($app_root)) {
       throw new \RuntimeException('Could not determine application root.');
