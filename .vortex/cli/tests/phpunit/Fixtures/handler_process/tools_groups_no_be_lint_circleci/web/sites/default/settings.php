@@ -14,10 +14,6 @@
  * environments.
  * @see https://www.vortextemplate.com/docs/drupal/settings
  *
- * phpcs:disable Drupal.Commenting.InlineComment.NoSpaceBefore
- * phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
- * phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
- * phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
  */
 
 declare(strict_types=1);
@@ -52,7 +48,6 @@
 
 $app_root ??= DRUPAL_ROOT;
 $site_path ??= 'sites/default';
-// @phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
 $contrib_path = $app_root . '/' . (is_dir($app_root . '/modules/contrib') ? 'modules/contrib' : 'modules');
 
 // Public files directory relative to the Drupal root.
