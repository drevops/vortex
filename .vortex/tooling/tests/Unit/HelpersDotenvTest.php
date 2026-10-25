<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for environment management functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\load_dotenv')]
#[Group('helpers')]
#[RunTestsInSeparateProcesses]
class HelpersDotenvTest extends UnitTestCase {

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

  #[DataProvider('dataProviderLoadDotenvPrecedence')]
  public function testLoadDotenvPrecedence(array $exported, string $env, ?string $env_local, array $expected): void {
    foreach ($exported as $key => $value) {
      self::envSet($key, $value);
    }

    $env_file = self::$tmp . '/.env';
    file_put_contents($env_file, $env);
    $files = [$env_file];

    if ($env_local !== NULL) {
      $env_local_file = self::$tmp . '/.env.local';
      file_put_contents($env_local_file, $env_local);
      $files[] = $env_local_file;
    }

    \DrevOps\VortexTooling\load_dotenv($files);

    foreach ($expected as $key => $expected_value) {
      $this->assertSame($expected_value, getenv($key), sprintf('Value of %s', $key));
    }
  }

  public static function dataProviderLoadDotenvPrecedence(): array {
    return [
      'file-only keys load' => [
        'exported' => [],
        'env' => "TEST_DOTENV_A=from_env\nTEST_DOTENV_B=from_env_b",
        'env_local' => NULL,
        'expected' => [
          'TEST_DOTENV_A' => 'from_env',
          'TEST_DOTENV_B' => 'from_env_b',
        ],
      ],
      'exported variable wins over .env' => [
        'exported' => ['TEST_DOTENV_A' => 'from_process'],
        'env' => "TEST_DOTENV_A=from_env\nTEST_DOTENV_B=from_env_b",
        'env_local' => NULL,
        'expected' => [
          'TEST_DOTENV_A' => 'from_process',
          'TEST_DOTENV_B' => 'from_env_b',
        ],
      ],
      'exported empty variable wins over .env' => [
        'exported' => ['TEST_DOTENV_A' => ''],
        'env' => 'TEST_DOTENV_A=from_env',
        'env_local' => NULL,
        'expected' => [
          'TEST_DOTENV_A' => '',
        ],
      ],
      '.env.local overrides .env' => [
        'exported' => [],
        'env' => "TEST_DOTENV_A=from_env\nTEST_DOTENV_B=from_env_b",
        'env_local' => 'TEST_DOTENV_A=from_env_local',
        'expected' => [
          'TEST_DOTENV_A' => 'from_env_local',
          'TEST_DOTENV_B' => 'from_env_b',
        ],
      ],
      'exported variable wins over .env and .env.local' => [
        'exported' => ['TEST_DOTENV_A' => 'from_process'],
        'env' => 'TEST_DOTENV_A=from_env',
        'env_local' => "TEST_DOTENV_A=from_env_local\nTEST_DOTENV_C=from_env_local_c",
        'expected' => [
          'TEST_DOTENV_A' => 'from_process',
          'TEST_DOTENV_C' => 'from_env_local_c',
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
