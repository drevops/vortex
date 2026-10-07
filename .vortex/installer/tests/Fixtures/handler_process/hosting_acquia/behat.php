@@ -75,8 +75,8 @@
   ->withExtension(new Extension(BehatStepsExtension::class, [
     'backends' => ['drupal', 'drush', 'blackbox'],
     'blackbox' => NULL,
-    'drupal' => ['drupal_root' => 'web'],
-    'drush' => ['root' => 'web'],
+    'drupal' => ['drupal_root' => 'docroot'],
+    'drush' => ['root' => 'docroot'],
     'regions' => [
       'header' => '#header',
       'primary_menu' => '.header-nav',
