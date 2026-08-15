@@ -11,7 +11,6 @@
 
 use Drupal\drupal_helpers\Helper;
 use Drupal\menu_link_content\MenuLinkContentInterface;
-use Drupal\testmode\Testmode;
 
 /**
  * Place counter block in the "content" region.
@@ -65,24 +64,4 @@
   ]);
 
   return 'Created "Pages" menu link in main navigation.';
-}
-
-/**
- * Configure testmode to filter the pages view.
- *
- * Registers the 'sw_demo_pages' view with testmode so that only
- * content matching the [TEST] prefix appears during test runs.
- *
- * @codeCoverageIgnore
- */
-function sw_demo_deploy_configure_testmode(): string {
-  $testmode = Testmode::getInstance();
-
-  $views = $testmode->getNodeViews();
-  if (!in_array('sw_demo_pages', $views)) {
-    $views[] = 'sw_demo_pages';
-    $testmode->setNodeViews($views);
-  }
-
-  return 'Configured testmode to filter the pages view.';
 }
