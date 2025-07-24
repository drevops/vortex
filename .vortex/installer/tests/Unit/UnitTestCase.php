<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Tests\Traits\FileAssertionsTrait;
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
  use FileAssertionsTrait;

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
  public static function locationsFixturesDir(): string {
    return '.vortex/installer/tests/Fixtures';
  }

  protected static function replaceVersions(string $directory): void {
    $regexes = [
      // composer.json and package.json.
      '/":\s*"(?:\^|~|>=?|<=?)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?"/' => '": "__VERSION__"',
      // Docker images with digests. Must come before regular docker image
      // pattern.
      '/([\w.-]+\/[\w.-]+:)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?@sha256:[a-f0-9]{64}/' => '${1}__VERSION__',
      // docker-compose.yml.
      '/([\w.-]+\/[\w.-]+:)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}__VERSION__',
      '/([\w.-]+\/[\w.-]+:)canary$/m' => '${1}__VERSION__',
      // GitHub Actions with digests (with optional version comments).
      '/([\w.-]+\/[\w.-]+)@[a-f0-9]{40}(\s*#\s*v[\d.]+)?/' => '${1}@__VERSION__',
      // GHAs.
      '/([\w.-]+\/[\w.-]+)@(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}@__VERSION__',
      '/(node-version:\s)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}__VERSION__',
    ];

    foreach ($regexes as $regex => $replace) {
      File::replaceContentInDir($directory, $regex, $replace);
    }
  }

}
