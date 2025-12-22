<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\Testing\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Testing\FileAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;
use AlexSkrypnyk\Snapshot\Testing\SnapshotTrait;
use DrevOps\VortexInstaller\Utils\Yaml;

/**
 * Class UnitTestCase.
 *
 * UnitTestCase fixture class.
 *
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    parent::tearDown();
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

    $content = file_get_contents($filename);
    if ($content === FALSE) {
      $this->fail(sprintf('Failed to read JSON file "%s".', $filename));
    }

    $this->assertJson($content, sprintf('JSON validation for file %s failed: %s', $filename, json_last_error_msg()));
  }

}
