<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Tests\Traits\TuiTrait;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Strings;

/**
 * Base class for functional tests.
 */
abstract class FunctionalTestCase extends UnitTestCase {

  use ApplicationTrait;
  use UpstreamTuiTrait;
  use TuiTrait;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    static::tuiSetUp();

    parent::setUpBeforeClass();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::tuiTearDown();

    if (empty(static::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    // Use SnapshotTrait's snapshotUpdateOnFailure() for automatic updates.
    if (str_contains(static::$fixtures, DIRECTORY_SEPARATOR . 'handler_process' . DIRECTORY_SEPARATOR)) {
      $this->snapshotUpdateOnFailure(static::$fixtures, static::$sut, static::$tmp);
    }

    parent::tearDown();
  }

  protected function snapshotUpdateBefore(string $actual): void {
    $this->replaceVersions($actual);
  }

  protected function runNonInteractiveInstall(?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $dst ??= static::$sut;

    $defaults = [
      InstallCommand::OPTION_NO_INTERACTION => TRUE,
      InstallCommand::OPTION_URI => File::dir(static::$root),
      InstallCommand::OPTION_DESTINATION => $dst,
    ];

    $options += $defaults;

    foreach ($options as $option => $value) {
      $args['--' . $option] = $value;
    }

    // Skip the database download in demo mode as it is not needed for the
    // installer's tests.
    Env::put(Config::IS_DEMO_DB_DOWNLOAD_SKIP, '1');

    $this->applicationRun($args, [], $expect_fail);
  }

  protected function runInteractiveInstall(array $answers = [], ?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $this->runNonInteractiveInstall($dst, $options + [InstallCommand::OPTION_NO_INTERACTION => FALSE], $expect_fail);
  }

  protected function assertSutContains(string|array $needles): void {
    $needles = is_array($needles) ? $needles : [$needles];

    foreach ($needles as $needle) {
      if (Strings::isRegex($needle)) {
        $this->assertDirectoryContainsString(static::$sut, $needle, [
          'scripts/vortex',
        ]);
      }
      else {
        $this->assertDirectoryContainsWord(static::$sut, $needle, [
          'scripts/vortex',
        ]);
      }
    }
  }

  protected function assertSutNotContains(string|array $needles): void {
    $needles = is_array($needles) ? $needles : [$needles];

    foreach ($needles as $needle) {
      if (Strings::isRegex($needle)) {
        $this->assertDirectoryNotContainsString(static::$sut, $needle, [
          'scripts/vortex',
        ]);
      }
      else {
        $this->assertDirectoryNotContainsWord(static::$sut, $needle, [
          'scripts/vortex',
        ]);
      }
    }
  }

  protected function replaceVersions(string $dir): void {
    File::getReplacer()
      ->addVersionReplacements()
      ->addExclusions(['127.0.0.1'])
      // Increase max replacements to handle large files with many version
      // strings (GHA workflows, lock files, etc). This value was empirically
      // derived through repeated trials.
      ->setMaxReplacements(5)
      ->replaceInDir($dir, ['scripts/vortex']);
  }

}
