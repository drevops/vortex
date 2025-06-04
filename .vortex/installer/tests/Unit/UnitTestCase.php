<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use DrevOps\VortexInstaller\Utils\File;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;

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
  protected static function locationsFixturesDir(): string {
    return '.vortex/installer/tests/Fixtures';
  }

  protected static function replaceVersions(string $directory): void {
    $regexes = [
      // composer.json and package.json.
      '/":\s*"(?:\^|~|>=?|<=?)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?"/' => '": "__VERSION__"',
      // docker-compose.yml.
      '/([\w.-]+\/[\w.-]+:)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}__VERSION__',
      '/([\w.-]+\/[\w.-]+:)canary$/m' => '${1}__VERSION__',
      // GHAs.
      '/([\w.-]+\/[\w.-]+)@(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}@__VERSION__',
    ];

    foreach ($regexes as $regex => $replace) {
      File::replaceContentInDir($directory, $regex, $replace);
    }
  }

}
