<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\Testing\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Testing\FileAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;
use AlexSkrypnyk\Snapshot\Testing\SnapshotTrait;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Yaml;

/**
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class UnitTestCase extends UpstreamUnitTestCase {

  use SerializableClosureTrait;
  use DirectoryAssertionsTrait;
  use FileAssertionsTrait;
  use SnapshotTrait;
  use EnvTrait;

  /**
   * The process environment before the test ran.
   *
   * @var array<string,string>
   */
  protected array $processEnvBackup;

  /**
   * The $_ENV superglobal before the test ran.
   *
   * @var array<string,mixed>
   */
  protected array $globalEnvBackup;

  /**
   * The $_SERVER superglobal before the test ran.
   *
   * @var array<string,mixed>
   */
  protected array $globalServerBackup;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->processEnvBackup = getenv();
    $this->globalEnvBackup = $_ENV;
    $this->globalServerBackup = $_SERVER;

    $cwd = getcwd();
    if ($cwd === FALSE) {
      throw new \RuntimeException('Failed to determine current working directory.');
    }

    // Run tests from the root of the repo.
    self::locationsInit($cwd . '/../../');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::envReset();
    $this->envRestore();
    parent::tearDown();
  }

  /**
   * Restore the process environment captured before the test ran.
   *
   * EnvTrait::envReset() only reverses names recorded by envSet(). Code under
   * test can call putenv() directly, and those values would otherwise be read
   * by every later test in the same process.
   */
  protected function envRestore(): void {
    foreach (array_keys(getenv()) as $name) {
      if (!array_key_exists($name, $this->processEnvBackup)) {
        putenv($name);
      }
    }

    foreach ($this->processEnvBackup as $name => $value) {
      if (getenv($name) !== $value) {
        putenv($name . '=' . $value);
      }
    }

    $_ENV = $this->globalEnvBackup;
    $_SERVER = $this->globalServerBackup;
  }

  /**
   * Unset the environment variables that a project's .env file defines.
   *
   * Handlers read the environment before the project's .env file, so a
   * variable exported by the shell running the suite would win over the
   * fixture.
   */
  protected static function envUnsetProjectVars(): void {
    static::envUnsetPrefix('VORTEX_');
    static::envUnsetPrefix('DRUPAL_');
    static::envUnsetPrefix('LAGOON_');
    static::envUnset('WEBROOT');
    static::envUnset('TZ');
  }

  /**
   * {@inheritdoc}
   */
  public static function locationsFixturesDir(): string {
    return '.vortex/installer/tests/Fixtures';
  }

  protected function assertYamlFileIsValid(string $filename): void {
    try {
      Yaml::validateFile($filename);
    }
    catch (\Exception $exception) {
      $this->fail(sprintf('YAML validation for file %s failed: %s', $filename, $exception->getMessage()));
    }
  }

  protected function assertJsonFileIsValid(string $filename): void {
    $this->assertFileExists($filename);

    $this->assertJson(File::read($filename), sprintf('JSON validation for file %s failed: %s', $filename, json_last_error_msg()));
  }

}
