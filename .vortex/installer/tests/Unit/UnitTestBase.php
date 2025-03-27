<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use AlexSkrypnyk\File\Internal\Index;
use DrevOps\Installer\Tests\Traits\ClosureWrapperTrait;
use DrevOps\Installer\Utils\File;

use AlexSkrypnyk\File\Tests\Unit\UnitTestBase as UpstreamUnitTestBase;

/**
 * Class UnitTestCase.
 *
 * UnitTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class UnitTestBase extends UpstreamUnitTestBase {

  use ClosureWrapperTrait;

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
    // Only update the fixtures for the 'install' tests.
    if (isset(self::$fixtures) && str_contains(self::$fixtures, DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR) && getenv('UPDATE_FIXTURES')) {
      $baseline = File::dir(static::$fixtures . '/../_baseline');
      // Use 'non-interactive' test run as a baseline.
      if (str_contains(self::$fixtures, 'non_interactive')) {
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
  protected static function locationsFixtures(): string {
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
