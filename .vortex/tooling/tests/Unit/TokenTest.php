<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for token replacement functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\replace_tokens')]
class TokenTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Load helpers to make functions available.
    require_once __DIR__ . '/../../src/helpers.php';
  }

  /**
   * Test replace_tokens() with various replacement scenarios.
   *
   * @param string $template
   *   Template string.
   * @param array<string, string> $replacements
   *   Replacements array.
   * @param string $expected
   *   Expected result.
   */
  #[DataProvider('providerReplaceTokens')]
  public function testReplaceTokens(string $template, array $replacements, string $expected): void {
    $result = \DrevOps\VortexTooling\replace_tokens($template, $replacements);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testReplaceTokens().
   *
   * @return array<string, array<string, mixed>>
   *   Test cases.
   */
  public static function providerReplaceTokens(): array {
    return [
      'single token' => [
        'template' => 'Hello %name%',
        'replacements' => ['name' => 'World'],
        'expected' => 'Hello World',
      ],
      'multiple tokens' => [
        'template' => '%greeting% %name%, you have %count% messages',
        'replacements' => ['greeting' => 'Hello', 'name' => 'Alice', 'count' => '5'],
        'expected' => 'Hello Alice, you have 5 messages',
      ],
      'no tokens' => [
        'template' => 'No tokens here',
        'replacements' => ['token' => 'value'],
        'expected' => 'No tokens here',
      ],
      'repeated tokens' => [
        'template' => '%token% and %token% again',
        'replacements' => ['token' => 'TEST'],
        'expected' => 'TEST and TEST again',
      ],
    ];
  }

}
