@@ -30,8 +30,6 @@
    * @return \PHPUnit\Framework\MockObject\MockObject
    *   An instance of the mock.
    *
-   * @SuppressWarnings("PHPMD.CyclomaticComplexity")
-   * @SuppressWarnings("PHPMD.ElseExpression")
    */
   protected function prepareMock(string $class, array $methods_map = [], array|bool $args = []): MockObject {
     $methods = array_values(array_filter(array_keys($methods_map)));
