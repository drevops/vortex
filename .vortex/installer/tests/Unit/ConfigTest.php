<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the Config class.
 */
#[CoversClass(Config::class)]
class ConfigTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    // Clear any existing environment variables that could interfere with tests.
    $constants = [
      Config::ROOT,
      Config::DST,
      Config::TMP,
      Config::REPO,
      Config::REF,
      Config::PROCEED,
      Config::IS_DEMO,
      Config::IS_DEMO_DB_DOWNLOAD_SKIP,
      Config::IS_VORTEX_PROJECT,
      Config::VERSION,
      Config::NO_INTERACTION,
      Config::QUIET,
      Config::NO_CLEANUP,
    ];

    foreach ($constants as $constant) {
      unset($_ENV[$constant]);
      if (getenv($constant) !== FALSE) {
        putenv($constant);
      }
    }
  }

  public function testConstructorDefaults(): void {
    $config = new Config();

    $this->assertEquals(File::cwd(), $config->getRoot());
    $this->assertEquals(File::cwd(), $config->getDst());
    $this->assertNotNull($config->get(Config::TMP));
  }

  public function testConstructorWithParameters(): void {
    $root = '/custom/root';
    $dst = '/custom/dst';
    $tmp = '/custom/tmp';

    $config = new Config($root, $dst, $tmp);

    $this->assertEquals($root, $config->getRoot());
    $this->assertEquals($dst, $config->getDst());
    $this->assertEquals($tmp, $config->get(Config::TMP));
  }

  public function testConstructorWithNullParameters(): void {
    $config = new Config(NULL, NULL, NULL);

    $this->assertEquals(File::cwd(), $config->getRoot());
    $this->assertEquals(File::cwd(), $config->getDst());
    $this->assertNotNull($config->get(Config::TMP));
  }

  #[DataProvider('dataProviderFromStringValid')]
  public function testFromStringValid(string $json, array $expectedValues): void {
    $config = Config::fromString($json);

    if (empty($expectedValues)) {
      // For empty JSON, just assert that config was created successfully.
      $this->assertInstanceOf(Config::class, $config);
    }
    else {
      foreach ($expectedValues as $key => $value) {
        $this->assertEquals($value, $config->get($key));
      }
    }
  }

  public static function dataProviderFromStringValid(): array {
    return [
      'empty_json' => [
        '{}',
        [],
      ],
      'single_value' => [
        '{"name": "test"}',
        ['NAME' => 'test'],
      ],
      'multiple_values' => [
        '{"name": "test", "version": "1.0.0", "debug": true}',
        ['NAME' => 'test', 'VERSION' => '1.0.0', 'DEBUG' => TRUE],
      ],
      'mixed_types' => [
        '{"string": "value", "number": 42, "boolean": true, "null": null}',
        ['STRING' => 'value', 'NUMBER' => 42, 'BOOLEAN' => TRUE],
      ],
      'nested_objects_and_arrays' => [
        '{"config": {"nested": "value"}, "list": [1, 2, 3]}',
        ['CONFIG' => ['nested' => 'value'], 'LIST' => [1, 2, 3]],
      ],
      'lowercase_keys_get_uppercased' => [
        '{"lowercase_key": "value", "MixedCase": "value2"}',
        ['LOWERCASE_KEY' => 'value', 'MIXEDCASE' => 'value2'],
      ],
    ];
  }

  #[DataProvider('dataProviderFromStringInvalid')]
  public function testFromStringInvalid(string $json, string $expectedError): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage($expectedError);

    Config::fromString($json);
  }

  public static function dataProviderFromStringInvalid(): array {
    return [
      'invalid_json' => [
        '{invalid json}',
        'Invalid configuration JSON string provided.',
      ],
      'non_object_json' => [
        '"just a string"',
        'Invalid configuration JSON string provided.',
      ],
      'array_json' => [
        '[1, 2, 3]',
        'Invalid key "0" in JSON string provided.',
      ],
      'numeric_json' => [
        '42',
        'Invalid configuration JSON string provided.',
      ],
      'boolean_json' => [
        'true',
        'Invalid configuration JSON string provided.',
      ],
      'null_json' => [
        'null',
        'Invalid configuration JSON string provided.',
      ],
      'numeric_key' => [
        '{"123": "value"}',
        'Invalid key "123" in JSON string provided.',
      ],
    ];
  }

  #[DataProvider('dataProviderGetAndSet')]
  public function testGetAndSet(string $name, mixed $value, mixed $default, mixed $expected): void {
    $config = new Config();

    // Test default behavior.
    $this->assertEquals($default, $config->get($name, $default));

    // Test setting and getting.
    $result = $config->set($name, $value);
    // Test fluent interface.
    $this->assertSame($config, $result);
    $this->assertEquals($expected, $config->get($name));
  }

  public static function dataProviderGetAndSet(): array {
    return [
      'string_value' => ['TEST_STRING', 'test_value', 'default', 'test_value'],
      'integer_value' => ['TEST_INT', 42, 0, 42],
      'boolean_true' => ['TEST_BOOL_TRUE', TRUE, FALSE, TRUE],
      'boolean_false' => ['TEST_BOOL_FALSE', FALSE, TRUE, FALSE],
      'null_value' => ['TEST_NULL', NULL, 'default', NULL],
      'array_value' => ['TEST_ARRAY', ['a', 'b', 'c'], [], ['a', 'b', 'c']],
      'object_value' => ['TEST_OBJECT', (object) ['key' => 'value'], NULL, (object) ['key' => 'value']],
    ];
  }

  public function testSetWithEnvironmentVariable(): void {
    $config = new Config();
    $envKey = 'TEST_ENV_VAR';
    $envValue = 'env_value';
    $setValue = 'set_value';

    // Set environment variable.
    putenv($envKey . '=' . $envValue);

    // Environment variable should take precedence.
    $config->set($envKey, $setValue);
    $this->assertEquals($envValue, $config->get($envKey));

    // Clean up.
    putenv($envKey);
  }

  public function testSetSkipEnvironment(): void {
    $config = new Config();
    $envKey = 'TEST_ENV_VAR_SKIP';
    $envValue = 'env_value';
    $setValue = 'set_value';

    // Set environment variable.
    putenv($envKey . '=' . $envValue);

    // Skip environment check.
    $config->set($envKey, $setValue, TRUE);
    $this->assertEquals($setValue, $config->get($envKey));

    // Clean up.
    putenv($envKey);
  }

  public function testGetRoot(): void {
    $root = '/test/root';
    $config = new Config($root);

    $this->assertEquals($root, $config->getRoot());
    $this->assertEquals($root, $config->get(Config::ROOT));
  }

  public function testGetDst(): void {
    $dst = '/test/dst';
    $config = new Config(NULL, $dst);

    $this->assertEquals($dst, $config->getDst());
    $this->assertEquals($dst, $config->get(Config::DST));
  }

  #[DataProvider('dataProviderIsQuiet')]
  public function testIsQuiet(mixed $value, bool $expected): void {
    $config = new Config();
    $config->set(Config::QUIET, $value);

    $this->assertEquals($expected, $config->isQuiet());
  }

  public static function dataProviderIsQuiet(): array {
    return [
      'boolean_true' => [TRUE, TRUE],
      'boolean_false' => [FALSE, FALSE],
      'string_true' => ['true', TRUE],
    // Non-empty string is truthy.
      'string_false' => ['false', TRUE],
      'string_empty' => ['', FALSE],
      'integer_zero' => [0, FALSE],
      'integer_non_zero' => [1, TRUE],
      'null' => [NULL, FALSE],
    ];
  }

  public function testSetQuiet(): void {
    $config = new Config();

    // Test default parameter (true).
    $config->setQuiet();
    $this->assertTrue($config->isQuiet());

    // Test explicit false.
    $config->setQuiet(FALSE);
    $this->assertFalse($config->isQuiet());

    // Test explicit true.
    $config->setQuiet(TRUE);
    $this->assertTrue($config->isQuiet());
  }

  #[DataProvider('dataProviderGetNoInteraction')]
  public function testGetNoInteraction(mixed $value, bool $expected): void {
    $config = new Config();
    $config->set(Config::NO_INTERACTION, $value);

    $this->assertEquals($expected, $config->getNoInteraction());
  }

  public static function dataProviderGetNoInteraction(): array {
    return [
      'boolean_true' => [TRUE, TRUE],
      'boolean_false' => [FALSE, FALSE],
      'string_true' => ['true', TRUE],
    // Non-empty string is truthy.
      'string_false' => ['false', TRUE],
      'string_empty' => ['', FALSE],
      'integer_zero' => [0, FALSE],
      'integer_non_zero' => [1, TRUE],
      'null' => [NULL, FALSE],
    ];
  }

  public function testSetNoInteraction(): void {
    $config = new Config();

    // Test default parameter (true).
    $config->setNoInteraction();
    $this->assertTrue($config->getNoInteraction());

    // Test explicit false.
    $config->setNoInteraction(FALSE);
    $this->assertFalse($config->getNoInteraction());

    // Test explicit true.
    $config->setNoInteraction(TRUE);
    $this->assertTrue($config->getNoInteraction());
  }

  #[DataProvider('dataProviderIsVortexProject')]
  public function testIsVortexProject(mixed $value, bool $expected): void {
    $config = new Config();
    $config->set(Config::IS_VORTEX_PROJECT, $value);

    $this->assertEquals($expected, $config->isVortexProject());
  }

  public static function dataProviderIsVortexProject(): array {
    return [
      'boolean_true' => [TRUE, TRUE],
      'boolean_false' => [FALSE, FALSE],
      'string_true' => ['true', TRUE],
    // Non-empty string is truthy.
      'string_false' => ['false', TRUE],
      'string_empty' => ['', FALSE],
      'integer_zero' => [0, FALSE],
      'integer_non_zero' => [1, TRUE],
      'null' => [NULL, FALSE],
    ];
  }

  public function testConstants(): void {
    // Test that all constants are defined and have expected values.
    $this->assertEquals('VORTEX_INSTALLER_ROOT_DIR', Config::ROOT);
    $this->assertEquals('VORTEX_INSTALLER_DST_DIR', Config::DST);
    $this->assertEquals('VORTEX_INSTALLER_TMP_DIR', Config::TMP);
    $this->assertEquals('VORTEX_INSTALLER_TEMPLATE_REPO', Config::REPO);
    $this->assertEquals('VORTEX_INSTALLER_TEMPLATE_REF', Config::REF);
    $this->assertEquals('VORTEX_INSTALLER_PROCEED', Config::PROCEED);
    $this->assertEquals('VORTEX_INSTALLER_IS_DEMO', Config::IS_DEMO);
    $this->assertEquals('VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP', Config::IS_DEMO_DB_DOWNLOAD_SKIP);
    $this->assertEquals('VORTEX_INSTALLER_IS_VORTEX_PROJECT', Config::IS_VORTEX_PROJECT);
    $this->assertEquals('VORTEX_INSTALLER_VERSION', Config::VERSION);
    $this->assertEquals('VORTEX_INSTALLER_NO_INTERACTION', Config::NO_INTERACTION);
    $this->assertEquals('VORTEX_INSTALLER_QUIET', Config::QUIET);
    $this->assertEquals('VORTEX_INSTALLER_NO_CLEANUP', Config::NO_CLEANUP);
  }

  public function testEnvironmentVariablePrecedenceInConstructor(): void {
    // Set environment variables.
    putenv(Config::ROOT . '=/env/root');
    putenv(Config::DST . '=/env/dst');
    putenv(Config::TMP . '=/env/tmp');

    $config = new Config('/param/root', '/param/dst', '/param/tmp');

    // Environment variables should take precedence for ROOT and TMP.
    $this->assertEquals('/env/root', $config->getRoot());
    // DST is set with skip_env=TRUE in constructor, so param value is used.
    $this->assertEquals('/param/dst', $config->getDst());
    $this->assertEquals('/env/tmp', $config->get(Config::TMP));

    // Clean up.
    putenv(Config::ROOT);
    putenv(Config::DST);
    putenv(Config::TMP);
  }

  public function testFluentInterface(): void {
    $config = new Config();

    $result = $config
      ->set('KEY1', 'value1')
      ->set('KEY2', 'value2')
      ->set('KEY3', 'value3');

    $this->assertSame($config, $result);
    $this->assertEquals('value1', $config->get('KEY1'));
    $this->assertEquals('value2', $config->get('KEY2'));
    $this->assertEquals('value3', $config->get('KEY3'));
  }

  public function testDefaultValues(): void {
    $config = new Config();

    // Test default values for boolean methods.
    $this->assertFalse($config->isQuiet());
    $this->assertFalse($config->getNoInteraction());
    $this->assertFalse($config->isVortexProject());
  }

}
