<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\Installer\Utils\Env;

/**
 * Class InstallerDotEnvTest.
 *
 * InstallerDotEnvTest fixture class.
 */
#[CoversClass(Env::class)]
#[RunTestsInSeparateProcesses]
class EnvTest extends UnitTestBase {

  /**
   * Backup value of the $GLOBALS['_SERVER'] variable.
   *
   * @var array
   */
  protected $backupServer;

  /**
   * Backup value of the $GLOBALS['_ENV'] variable.
   *
   * @var array
   */
  protected $backupEnv;

  protected function setUp(): void {
    $this->backupEnv = $GLOBALS['_ENV'];
    $this->backupServer = $GLOBALS['_SERVER'];

    parent::setUp();
  }

  protected function tearDown(): void {
    $GLOBALS['_ENV'] = $this->backupEnv;
    $GLOBALS['_SERVER'] = $this->backupServer;

    parent::tearDown();
  }

  #[DataProvider('dataProviderGet')]
  public function testGet(string $name, string $value, ?string $default, ?string $expected): void {
    putenv(sprintf('%s=%s', $name, $expected));

    $this->assertSame($expected, Env::get($name, $default));

    putenv($name);
  }

  public static function dataProviderGet(): array {
    return [
      ['VAR', 'VAL1', 'DEF1', 'VAL1'],
      ['VAR', 'VAL1', NULL, 'VAL1'],
      ['VAR', 'VAL1', 'VAL2', 'VAL1'],
      ['VAR', '', 'DEF1', 'DEF1'],
      ['VAR', '', NULL, NULL],
      ['VAR', '', 'VAL2', 'VAL2'],
    ];
  }

  #[DataProvider('dataProviderGetFromDotenv')]
  public function testGetFromDotenv(string $name, ?string $value, ?string $value_dotenv, ?string $expected): void {
    putenv(sprintf('%s=%s', $name, $expected));

    $content = '';
    if ($value_dotenv) {
      $content = sprintf('%s=%s', $name, $value_dotenv);
    }
    $filename = $this->createFixtureEnvFile($content);

    if (!$filename) {
      $this->fail('Failed to create fixture file.');
    }

    $actual = Env::getFromDotenv($name, dirname($filename));
    $this->assertEquals($expected, $actual);

    putenv($name);
  }

  public static function dataProviderGetFromDotenv(): array {
    return [
      ['VAR', 'VAL1', NULL, 'VAL1'],
      ['VAR', 'VAL1', 'VALDOTENV1', 'VAL1'],
      ['VAR', NULL, 'VALDOTENV1', 'VALDOTENV1'],
      ['VAR', NULL, NULL, NULL],
    ];
  }

  #[DataProvider('dataProviderPutFromDotenv')]
  public function testPutFromDotenv(string $name, ?string $value, ?string $value_dotenv, bool $override_existing, ?string $expected): void {
    if ($value) {
      putenv(sprintf('%s=%s', $name, $value));
      $GLOBALS['_ENV'][$name] = $value;
      $GLOBALS['_SERVER'][$name] = $value;
    }

    $content = '';
    if ($value_dotenv) {
      $content = sprintf('%s=%s', $name, $value_dotenv);
    }
    $filename = $this->createFixtureEnvFile($content);

    if (!$filename) {
      $this->fail('Failed to create fixture file.');
    }

    Env::putFromDotenv($filename, $override_existing);

    $this->assertEquals($expected, getenv($name));
    $this->assertEquals($GLOBALS['_ENV'][$name], $expected);
    $this->assertEquals($GLOBALS['_SERVER'][$name], $expected);
  }

  public static function dataProviderPutFromDotenv(): array {
    return [
      ['VAR', 'VAL1', NULL, FALSE, 'VAL1'],
      ['VAR', 'VAL1', 'VALDOTENV1', FALSE, 'VAL1'],
      ['VAR', NULL, 'VALDOTENV1', FALSE, 'VALDOTENV1'],
      ['VAR', NULL, NULL, FALSE, NULL],

      ['VAR', 'VAL1', NULL, TRUE, 'VAL1'],
      ['VAR', 'VAL1', 'VALDOTENV1', TRUE, 'VALDOTENV1'],
      ['VAR', NULL, 'VALDOTENV1', TRUE, 'VALDOTENV1'],
      ['VAR', NULL, NULL, TRUE, NULL],
    ];
  }

  protected function createFixtureEnvFile(string $content): string|false {
    $filename = tempnam(sys_get_temp_dir(), '.env');
    file_put_contents($filename, $content);

    return $filename;
  }

}
