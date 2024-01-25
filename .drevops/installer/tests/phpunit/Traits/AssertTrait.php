<?php

namespace Drevops\Installer\Tests\Traits;

/**
 *
 */
trait AssertTrait {

  /**
   * Asserts that a string contains or does not contain a set of substrings.
   *
   * This function can accept multiple expected substrings. If any expected
   * substring starts with "- ", the assertion will check that the actual string
   * does NOT contain that substring (after removing the "- " prefix).
   * Otherwise, it asserts that the actual string does contain the expected
   * substring.
   *
   * Examples:
   *   - assertStringContains($actual, 'hello') will assert that $actual
   *     contains 'hello'.
   *   - assertStringContains($actual, '- hello') will assert that $actual
   *     does NOT contain 'hello'.
   *   - assertStringContains($actual, 'hello', 'world', '- goodbye') will
   *     assert that $actual contains 'hello' and 'world', but does NOT contain
   *     'goodbye'.
   *
   * @param string $actual
   *   The actual string to be tested.
   * @param string ...$expected
   *   Varied number of expected substrings.
   */
  public function assertStringContains($actual, ...$expected): void {
    foreach ($expected as $expected_item) {
      if (str_starts_with($expected_item, '- ')) {
        $this->assertStringNotContainsString(substr($expected_item, 2), $actual);
      }
      else {
        $this->assertStringContainsString($expected_item, $actual);
      }
    }
  }

}
