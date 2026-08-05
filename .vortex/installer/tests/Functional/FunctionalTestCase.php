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
use AlexSkrypnyk\File\Replacer\Replacement;
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

  protected function runNonInteractiveInstall(?string $destination = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $destination ??= static::$sut;

    $defaults = [
      InstallCommand::OPTION_NO_INTERACTION => TRUE,
      InstallCommand::OPTION_URI => File::dir(static::$root),
      InstallCommand::OPTION_DESTINATION => $destination,
    ];

    $options += $defaults;

    foreach ($options as $option => $value) {
      $args['--' . $option] = $value;
    }

    // Skip the database fetch in demo mode as it is not needed for the
    // installer's tests.
    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    $this->applicationRun($args, [], $expect_fail);
  }

  protected function assertSutContains(string|array $needles): void {
    $needles = is_array($needles) ? $needles : [$needles];

    foreach ($needles as $needle) {
      if (Strings::isRegex($needle)) {
        $this->assertDirectoryContainsString(static::$sut, $needle);
      }
      else {
        $this->assertDirectoryContainsWord(static::$sut, $needle);
      }
    }
  }

  protected function assertSutNotContains(string|array $needles): void {
    $needles = is_array($needles) ? $needles : [$needles];

    foreach ($needles as $needle) {
      if (Strings::isRegex($needle)) {
        $this->assertDirectoryNotContainsString(static::$sut, $needle);
      }
      else {
        $this->assertDirectoryNotContainsWord(static::$sut, $needle);
      }
    }
  }

  protected function replaceVersions(string $dir): void {
    File::getReplacer()
      ->addVersionReplacements()
      // PHPStan phpVersion is an integer (e.g., 80330), not semver.
      ->addReplacement(Replacement::create('phpstan_version', '/(phpVersion:\s)\d{5,6}/', '${1}' . Replacement::VERSION))
      // The Vortex badge carries the checked-out ref, so every fixture churns
      // on a tagged checkout unless it is masked like any other version stamp.
      ->addReplacement(Replacement::create('vortex_badge', '#(badge/Vortex-)[^-]+(-65ACBC\.svg)#', '${1}' . Replacement::VERSION . '${2}'))
      ->addReplacement(Replacement::create('vortex_badge_url', '#(github\.com/drevops/vortex/tree/)\S+?(\))#', '${1}' . Replacement::VERSION . '${2}'))
      ->addExclusions(['127.0.0.1'])
      // Increase max replacements to handle large files with many version
      // strings (GHA workflows, lock files, etc). This value was empirically
      // derived through repeated trials.
      ->setMaxReplacements(5)
      ->replaceInDir($dir);
  }

}
