@@ -28,11 +28,9 @@
 use DrevOps\BehatSteps\Steps\Drupal\ModuleTrait;
 use DrevOps\BehatSteps\Steps\Drupal\ParagraphsTrait;
 use DrevOps\BehatSteps\Steps\Drupal\QueueTrait;
-use DrevOps\BehatSteps\Steps\Drupal\RedirectTrait;
 use DrevOps\BehatSteps\Steps\Drupal\SearchApiTrait;
 use DrevOps\BehatSteps\Steps\Drupal\StateTrait;
 use DrevOps\BehatSteps\Steps\Drupal\TaxonomyTrait;
-use DrevOps\BehatSteps\Steps\Drupal\TestmodeTrait;
 use DrevOps\BehatSteps\Steps\Drupal\UserTrait;
 use DrevOps\BehatSteps\Steps\Drupal\WatchdogTrait;
 use DrevOps\BehatSteps\Steps\Generic\AccessibilityTrait;
@@ -108,7 +106,6 @@
   use PathTrait;
   use QueueTrait;
   use RandomTrait;
-  use RedirectTrait;
   use RegionTrait;
   use ResponseTrait;
   use ResponsiveTrait;
@@ -117,7 +114,6 @@
   use StateTrait;
   use TableTrait;
   use TaxonomyTrait;
-  use TestmodeTrait;
   use UserTrait;
   use WaitTrait;
   use WatchdogTrait;
