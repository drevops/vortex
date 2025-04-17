<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use AlexSkrypnyk\File\Internal\Index;
use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use DrevOps\Installer\Utils\File;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;

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

  protected function tearDown(): void {
    if (empty(self::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $is_failure = $this->status() instanceof Failure || $this->status() instanceof Error;
    $has_message = str_contains($this->status()->message(), 'Differences between directories') || str_contains($this->status()->message(), 'Failed to apply patch');
    $fixture_exists = str_contains(self::$fixtures, DIRECTORY_SEPARATOR . 'init' . DIRECTORY_SEPARATOR);
    $update_requested = getenv('UPDATE_FIXTURES');

    if ($is_failure && $has_message && $fixture_exists && $update_requested) {
      $baseline = File::dir(static::$fixtures . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . self::BASELINE_DIR);
      if (str_contains(self::$fixtures, 'baseline')) {
        File::copyIfExists($baseline . DIRECTORY_SEPARATOR . Index::IGNORECONTENT, self::$sut . DIRECTORY_SEPARATOR . Index::IGNORECONTENT);
        File::copyIfExists($baseline . DIRECTORY_SEPARATOR . Index::IGNORECONTENT, self::$tmp . DIRECTORY_SEPARATOR . Index::IGNORECONTENT);
        File::rmdir($baseline);
        File::sync(self::$sut, $baseline);
        static::replaceVersions($baseline);
        File::copyIfExists(static::$tmp . DIRECTORY_SEPARATOR . Index::IGNORECONTENT, $baseline . DIRECTORY_SEPARATOR . Index::IGNORECONTENT);
      }
      File::copyIfExists(self::$fixtures . DIRECTORY_SEPARATOR . Index::IGNORECONTENT, self::$tmp . DIRECTORY_SEPARATOR . Index::IGNORECONTENT);
      File::rmdir(self::$fixtures);
      File::diff($baseline, self::$sut, self::$fixtures);
      File::copyIfExists(self::$tmp . DIRECTORY_SEPARATOR . Index::IGNORECONTENT, self::$fixtures . DIRECTORY_SEPARATOR . Index::IGNORECONTENT);
    }

    parent::tearDown();
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
