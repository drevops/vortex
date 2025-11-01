<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use DrevOps\VortexInstaller\Utils\JsonManipulator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Seld\JsonLint\ParsingException;

#[CoversClass(JsonManipulator::class)]
class JsonManipulatorTest extends UnitTestCase {

  /**
   * Sample JSON content for testing.
   */
  private const string SAMPLE_JSON = '{
    "name": "test/package",
    "description": "A test package",
    "version": "1.0.0",
    "require": {
      "php": "^8.1",
      "symfony/console": "^6.0"
    },
    "require-dev": {
      "phpunit/phpunit": "^9.0"
    },
    "autoload": {
      "psr-4": {
        "Test\\\\": "src/"
      }
    },
    "scripts": {
      "test": "phpunit"
    }
  }';

  /**
   * Invalid JSON content for testing error cases.
   */
  private const string INVALID_JSON = '{
    "name": "test/package",
    "description": "A test package"
    "invalid": missing comma
  }';

  /**
   * Create a temporary JSON file for testing.
   */
  private function createTempJsonFile(string $content): string {
    $temp_file = tempnam(sys_get_temp_dir(), 'json_test_');
    file_put_contents($temp_file, $content);
    return $temp_file;
  }

  public function testConstructor(): void {
    $manipulator = new JsonManipulator(self::SAMPLE_JSON);
    $this->assertInstanceOf(JsonManipulator::class, $manipulator);
  }

  public function testFromFileWithValidFile(): void {
    $temp_file = $this->createTempJsonFile(self::SAMPLE_JSON);

    try {
      $manipulator = JsonManipulator::fromFile($temp_file);
      $this->assertInstanceOf(JsonManipulator::class, $manipulator);
    }
    finally {
      unlink($temp_file);
    }
  }

  public function testFromFileWithNonexistentFile(): void {
    $manipulator = JsonManipulator::fromFile('/nonexistent/file.json');
    $this->assertNull($manipulator);
  }

  public function testFromFileWithNonReadableFile(): void {
    $temp_file = $this->createTempJsonFile(self::SAMPLE_JSON);
    chmod($temp_file, 0000);

    try {
      $manipulator = JsonManipulator::fromFile($temp_file);
      $this->assertNull($manipulator);
    }
    finally {
      chmod($temp_file, 0644);
      unlink($temp_file);
    }
  }

  public function testFromFileWithDirectory(): void {
    $temp_dir = sys_get_temp_dir() . '/json_test_dir_' . uniqid();
    mkdir($temp_dir);

    try {
      $manipulator = JsonManipulator::fromFile($temp_dir);
      $this->assertNull($manipulator);
    }
    finally {
      rmdir($temp_dir);
    }
  }

  #[DataProvider('dataProviderGetProperty')]
  public function testGetProperty(string $property_name, mixed $expected): void {
    $manipulator = new JsonManipulator(self::SAMPLE_JSON);
    $result = $manipulator->getProperty($property_name);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderGetProperty(): array {
    return [
      // Top-level properties.
      'name property' => ['name', 'test/package'],
      'description property' => ['description', 'A test package'],
      'version property' => ['version', '1.0.0'],

      // Nested object properties.
      'require.php' => ['require.php', '^8.1'],
      'require.symfony/console' => ['require.symfony/console', '^6.0'],
      'require-dev.phpunit/phpunit' => ['require-dev.phpunit/phpunit', '^9.0'],
      'autoload.psr-4.Test\\' => ['autoload.psr-4.Test\\', 'src/'],
      'scripts.test' => ['scripts.test', 'phpunit'],

      // Entire objects.
      'require object' => [
        'require',
        ['php' => '^8.1', 'symfony/console' => '^6.0'],
      ],
      'autoload.psr-4 object' => [
        'autoload.psr-4',
        ['Test\\' => 'src/'],
      ],

      // Non-existent properties.
      'nonexistent top-level' => ['nonexistent', NULL],
      'nonexistent nested' => ['require.nonexistent', NULL],
      'nonexistent deep nested' => ['require.nested.deep', NULL],
      'empty property name' => ['', NULL],

      // Edge cases with dots.
      'property with trailing dot' => ['require.', NULL],
      'property with multiple dots' => ['require..php', NULL],
    ];
  }

  public function testGetPropertyWithInvalidJson(): void {
    $this->expectException(ParsingException::class);

    $manipulator = new JsonManipulator(self::INVALID_JSON);
    $manipulator->getProperty('name');
  }

  public function testGetPropertyWithEmptyJson(): void {
    $manipulator = new JsonManipulator('{}');
    $result = $manipulator->getProperty('name');
    $this->assertNull($result);
  }

  public function testGetPropertyWithNullValue(): void {
    $json_with_null = '{"nullable": null}';
    $manipulator = new JsonManipulator($json_with_null);
    $result = $manipulator->getProperty('nullable');
    $this->assertNull($result);
  }

  public function testGetPropertyWithArrayValue(): void {
    $json_with_array = '{"items": ["item1", "item2", "item3"]}';
    $manipulator = new JsonManipulator($json_with_array);
    $result = $manipulator->getProperty('items');
    $this->assertSame(['item1', 'item2', 'item3'], $result);
  }

  public function testGetPropertyWithNumericValue(): void {
    $json_with_numbers = '{"integer": 42, "float": 3.14, "zero": 0}';
    $manipulator = new JsonManipulator($json_with_numbers);

    $this->assertSame(42, $manipulator->getProperty('integer'));
    $this->assertSame(3.14, $manipulator->getProperty('float'));
    $this->assertSame(0, $manipulator->getProperty('zero'));
  }

  public function testGetPropertyWithBooleanValue(): void {
    $json_with_booleans = '{"true_value": true, "false_value": false}';
    $manipulator = new JsonManipulator($json_with_booleans);

    $this->assertTrue($manipulator->getProperty('true_value'));
    $this->assertFalse($manipulator->getProperty('false_value'));
  }

  public function testGetPropertyDeepNesting(): void {
    $deep_json = '{
      "level1": {
        "level2": {
          "level3": {
            "level4": {
              "deep_value": "found"
            }
          }
        }
      }
    }';

    $manipulator = new JsonManipulator($deep_json);
    $result = $manipulator->getProperty('level1.level2.level3.level4.deep_value');
    $this->assertSame('found', $result);
  }

  public function testGetPropertyArrayAccess(): void {
    $json_with_arrays = '{
      "users": [
        {"name": "John", "age": 30},
        {"name": "Jane", "age": 25}
      ]
    }';

    $manipulator = new JsonManipulator($json_with_arrays);

    // Should return the entire array.
    $users = $manipulator->getProperty('users');
    $this->assertIsArray($users);
    $this->assertCount(2, $users);

    // Test accessing array indices with dot notation.
    $result = $manipulator->getProperty('users.0');
    $this->assertSame(['name' => 'John', 'age' => 30], $result);

    // Test accessing primitive values inside objects inside arrays.
    $john_name = $manipulator->getProperty('users.0.name');
    $this->assertSame('John', $john_name);

    $jane_age = $manipulator->getProperty('users.1.age');
    $this->assertSame(25, $jane_age);
  }

}
