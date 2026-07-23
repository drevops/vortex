<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Traits;

/**
 * Trait AssertTrait.
 *
 * Provides custom assertions.
 *
 * @codeCoverageIgnore
 */
trait AssertTrait {

  /**
   * Assert that a string is present in an array.
   *
   * @param string $needle
   *   The string to search for.
   * @param array $haystack
   *   The array to search in.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   If the string is not present in the array.
   */
  public function assertArrayContainsString(string $needle, array $haystack): void {
    foreach ($haystack as $hay) {
      if (str_contains((string) $hay, $needle)) {
        $this->addToAssertionCount(1);

        return;
      }
    }
    $this->fail(sprintf('Failed asserting that string "%s" is present in array %s.', $needle, print_r($haystack, TRUE)));
  }

}
