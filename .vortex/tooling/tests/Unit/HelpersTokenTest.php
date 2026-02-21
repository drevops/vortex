<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for token replacement functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\replace_tokens')]
#[Group('helpers')]
class HelpersTokenTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderReplaceTokens')]
  public function testReplaceTokens(string $template, array $replacements, string $expected): void {
    $result = \DrevOps\VortexTooling\replace_tokens($template, $replacements);
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderReplaceTokens(): array {
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
      'empty template' => [
        'template' => '',
        'replacements' => ['name' => 'World'],
        'expected' => '',
      ],
      'empty replacements' => [
        'template' => 'Hello %name%',
        'replacements' => [],
        'expected' => 'Hello %name%',
      ],
      'value with double quotes' => [
        'template' => '%msg%',
        'replacements' => ['msg' => '"hi"'],
        'expected' => '\"hi\"',
      ],
      'value with newline' => [
        'template' => '%msg%',
        'replacements' => ['msg' => "a\nb"],
        'expected' => 'a\nb',
      ],
      'value with backslash' => [
        'template' => '%msg%',
        'replacements' => ['msg' => 'a\\b'],
        'expected' => 'a\\\\b',
      ],
      'value with mixed special chars' => [
        'template' => '%v%',
        'replacements' => ['v' => "She said \"hi\"\n"],
        'expected' => 'She said \"hi\"\n',
      ],
      'unicode in token name' => [
        'template' => 'Hello %名前%',
        'replacements' => ['名前' => 'World'],
        'expected' => 'Hello World',
      ],
      'unicode in value' => [
        'template' => 'Hello %name%',
        'replacements' => ['name' => 'Wörld'],
        'expected' => 'Hello W\u00f6rld',
      ],
    ];
  }

}
