<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Unit;

use Drupal\Tests\ys_base\Traits\AssertTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the AssertTrait shipped to consumer sites.
 */
class AssertTraitTest extends TestCase {

  use AssertTrait;

  /**
   * Tests that a matching string passes the assertion.
   */
  #[DataProvider('dataProviderAssertArrayContainsString')]
  public function testAssertArrayContainsString(string $needle, array $haystack): void {
    $this->assertArrayContainsString($needle, $haystack);
  }

  /**
   * Data provider for testAssertArrayContainsString().
   */
  public static function dataProviderAssertArrayContainsString(): \Iterator {
    yield 'exact match' => ['first', ['first', 'second']];

    yield 'substring match' => ['irs', ['first', 'second']];

    yield 'match in a later element' => ['second', ['first', 'second']];

    yield 'string keys ignored' => ['first', ['a' => 'first']];

    yield 'integer element cast to string' => ['42', [42]];

    yield 'stringable element cast to string' => ['first', [new AssertStringableStub()]];
  }

  /**
   * Tests that a missing string fails the assertion.
   */
  #[DataProvider('dataProviderAssertArrayContainsStringFails')]
  public function testAssertArrayContainsStringFails(string $needle, array $haystack): void {
    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage(sprintf('Failed asserting that string "%s" is present in array', $needle));

    $this->assertArrayContainsString($needle, $haystack);
  }

  /**
   * Data provider for testAssertArrayContainsStringFails().
   */
  public static function dataProviderAssertArrayContainsStringFails(): \Iterator {
    yield 'empty haystack' => ['first', []];

    yield 'no match' => ['third', ['first', 'second']];

    yield 'case mismatch' => ['FIRST', ['first']];
  }

}

/**
 * Stub returning a string from its string conversion.
 */
class AssertStringableStub implements \Stringable {

  /**
   * Returns the value the assertion searches.
   */
  public function __toString(): string {
    return 'first';
  }

}
