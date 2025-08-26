@@ -15,10 +15,6 @@
  * The main purpose of these tests is to ensure that the settings and configs
  * appear in every environment as expected.
  *
- * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
- * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
- * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.AfterLast
- * phpcs:disable Drupal.Classes.ClassDeclaration.CloseBraceAfterBody
  */
 #[Group('drupal_settings')]
 class EnvironmentSettingsTest extends SettingsTestCase {
@@ -266,9 +262,9 @@
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
