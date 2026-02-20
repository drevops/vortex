<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for string_map_to_array() function.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\string_map_to_array')]
#[Group('helpers')]
class HelpersStringMapTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderStringMapToArray')]
  public function testStringMapToArray(
    string $map,
    string $separator,
    string $key_value_separator,
    ?array $expected,
    ?string $expected_error,
  ): void {
    if ($expected_error !== NULL) {
      $this->mockQuit(1);
      $this->expectException(QuitErrorException::class);
      $this->expectOutputRegex('/' . preg_quote($expected_error, '/') . '/');
    }

    $result = \DrevOps\VortexTooling\string_map_to_array($map, $separator, $key_value_separator);

    if ($expected !== NULL) {
      $this->assertEquals($expected, $result);
    }
  }

  public static function dataProviderStringMapToArray(): array {
    return [
      'single pair' => [
        'map' => 'key=value',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => ['key' => 'value'],
        'expected_error' => NULL,
      ],
      'multiple pairs' => [
        'map' => 'key1=value1,key2=value2,key3=value3',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
        'expected_error' => NULL,
      ],
      'with spaces around separator' => [
        'map' => 'key1=value1 , key2=value2',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => ['key1' => 'value1', 'key2' => 'value2'],
        'expected_error' => NULL,
      ],
      'with spaces around key value separator' => [
        'map' => 'key1 = value1,key2 = value2',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => ['key1' => 'value1', 'key2' => 'value2'],
        'expected_error' => NULL,
      ],
      'custom separator' => [
        'map' => 'key1=value1;key2=value2',
        'separator' => ';',
        'key_value_separator' => '=',
        'expected' => ['key1' => 'value1', 'key2' => 'value2'],
        'expected_error' => NULL,
      ],
      'custom key value separator' => [
        'map' => 'key1:value1,key2:value2',
        'separator' => ',',
        'key_value_separator' => ':',
        'expected' => ['key1' => 'value1', 'key2' => 'value2'],
        'expected_error' => NULL,
      ],
      'custom both separators' => [
        'map' => 'key1:value1|key2:value2',
        'separator' => '|',
        'key_value_separator' => ':',
        'expected' => ['key1' => 'value1', 'key2' => 'value2'],
        'expected_error' => NULL,
      ],
      'value contains equals sign' => [
        'map' => 'key=value=with=equals',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => ['key' => 'value=with=equals'],
        'expected_error' => NULL,
      ],
      'invalid pair missing value' => [
        'map' => 'key1=value1,invalid,key2=value2',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => NULL,
        'expected_error' => 'invalid key/value pair "invalid" provided.',
      ],
      'invalid pair no separator' => [
        'map' => 'keyvalue',
        'separator' => ',',
        'key_value_separator' => '=',
        'expected' => NULL,
        'expected_error' => 'invalid key/value pair "keyvalue" provided.',
      ],
    ];
  }

}
