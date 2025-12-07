<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for environment management functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\load_dotenv')]
#[Group('helpers')]
class DotenvTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderLoadDotenv')]
  public function testLoadDotenv(array $env_content, array $expected): void {
    $env_file = self::$tmp . '/.env.test';
    $lines = [];
    foreach ($env_content as $key => $value) {
      $lines[] = sprintf('%s=%s', $key, $value);
    }
    file_put_contents($env_file, implode("\n", $lines));

    \DrevOps\VortexTooling\load_dotenv([$env_file]);

    foreach ($expected as $key => $expected_value) {
      $this->assertEquals($expected_value, getenv($key));
    }
  }

  public static function dataProviderLoadDotenv(): array {
    return [
      'simple values' => [
        'env_content' => [
          'TEST_VAR_1' => 'value1',
          'TEST_VAR_2' => 'value2',
        ],
        'expected' => [
          'TEST_VAR_1' => 'value1',
          'TEST_VAR_2' => 'value2',
        ],
      ],
      'quoted values' => [
        'env_content' => [
          'TEST_QUOTED_DOUBLE' => '"double quoted"',
          'TEST_QUOTED_SINGLE' => "'single quoted'",
        ],
        'expected' => [
          'TEST_QUOTED_DOUBLE' => 'double quoted',
          'TEST_QUOTED_SINGLE' => 'single quoted',
        ],
      ],
      'values with equals' => [
        'env_content' => [
          'TEST_EQUALS' => 'value=with=equals',
        ],
        'expected' => [
          'TEST_EQUALS' => 'value=with=equals',
        ],
      ],
      'values with spaces' => [
        'env_content' => [
          'TEST_SPACES' => '  value with spaces  ',
        ],
        'expected' => [
          'TEST_SPACES' => 'value with spaces',
        ],
      ],
    ];
  }

  public function testLoadDotenvSkipsCommentsAndEmpty(): void {
    $env_file = self::$tmp . '/.env.test';
    file_put_contents($env_file, "# Comment\nTEST_VAR=value\n\n# Another comment\nTEST_VAR2=value2");

    \DrevOps\VortexTooling\load_dotenv([$env_file]);

    $this->assertEquals('value', getenv('TEST_VAR'));
    $this->assertEquals('value2', getenv('TEST_VAR2'));
  }

  public function testLoadDotenvNonExistentFile(): void {
    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\load_dotenv([self::$tmp . '/.env.nonexistent']);
  }

}
