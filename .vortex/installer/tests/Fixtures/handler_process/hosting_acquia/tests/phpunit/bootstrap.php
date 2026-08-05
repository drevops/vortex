@@ -20,10 +20,10 @@
 
 // Drupal core does not create the browser output directory, so browser tests
 // fail to write their HTML output unless it already exists.
-$browser_output_dir = dirname(__DIR__, 2) . '/web/sites/simpletest/browser_output';
+$browser_output_dir = dirname(__DIR__, 2) . '/docroot/sites/simpletest/browser_output';
 if (!is_dir($browser_output_dir)) {
   mkdir($browser_output_dir, 0775, TRUE);
 }
 
 // Load the Drupal core test bootstrap.
-require dirname(__DIR__, 2) . '/web/core/tests/bootstrap.php';
+require dirname(__DIR__, 2) . '/docroot/core/tests/bootstrap.php';
