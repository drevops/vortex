@@ -19,10 +19,10 @@
 }
 
 // @see https://www.drupal.org/project/drupal/issues/2992069
-$browser_output_dir = dirname(__DIR__, 2) . '/web/sites/simpletest/browser_output';
+$browser_output_dir = dirname(__DIR__, 2) . '/docroot/sites/simpletest/browser_output';
 if (!is_dir($browser_output_dir)) {
   mkdir($browser_output_dir, 0775, TRUE);
 }
 
 // Load the Drupal core test bootstrap.
-require dirname(__DIR__, 2) . '/web/core/tests/bootstrap.php';
+require dirname(__DIR__, 2) . '/docroot/core/tests/bootstrap.php';
