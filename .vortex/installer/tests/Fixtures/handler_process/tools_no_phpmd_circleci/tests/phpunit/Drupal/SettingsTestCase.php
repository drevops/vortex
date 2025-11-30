@@ -129,7 +129,6 @@
    * @param array $vars
    *   Array of environment variables.
    *
-   * @SuppressWarnings("PHPMD.ElseExpression")
    */
   protected function setEnvVars(array $vars): void {
     // Unset the existing environment variable if not set in the test.
@@ -308,7 +307,6 @@
    * @param string $message
    *   Message to display on failure.
    *
-   * @SuppressWarnings("PHPMD.ElseExpression")
    */
   protected function assertArraySubset(array $subset, array $haystack, string $message = ''): void {
     foreach ($subset as $key => $value) {
