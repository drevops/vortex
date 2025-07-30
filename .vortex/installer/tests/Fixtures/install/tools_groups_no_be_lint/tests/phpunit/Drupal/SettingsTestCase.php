@@ -11,7 +11,6 @@
  *
  * Base class for testing Drupal settings.
  *
- *  phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
  */
 abstract class SettingsTestCase extends TestCase {
 
@@ -129,7 +128,6 @@
    * @param array $vars
    *   Array of environment variables.
    *
-   * @SuppressWarnings("PHPMD.ElseExpression")
    */
   protected function setEnvVars(array $vars): void {
     // Unset the existing environment variable if not set in the test.
@@ -308,7 +306,6 @@
    * @param string $message
    *   Message to display on failure.
    *
-   * @SuppressWarnings("PHPMD.ElseExpression")
    */
   protected function assertArraySubset(array $subset, array $haystack, string $message = ''): void {
     foreach ($subset as $key => $value) {
