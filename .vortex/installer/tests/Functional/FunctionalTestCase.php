<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional;

use AlexSkrypnyk\File\Internal\Index;
use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Tests\Traits\TuiTrait;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Strings;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;

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

    $is_failure = $this->status() instanceof Failure || $this->status() instanceof Error;
    $has_message = str_contains($this->status()->message(), 'Differences between directories') || str_contains($this->status()->message(), 'Failed to apply patch');
    $fixture_exists = str_contains(static::$fixtures, DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR);
    $update_requested = getenv('UPDATE_FIXTURES');

    if ($is_failure && $has_message && $fixture_exists && $update_requested) {
      fwrite(STDERR, PHP_EOL . '[INFO] Feature update requested' . PHP_EOL);
      $baseline = File::dir(static::$fixtures . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . static::BASELINE_DIR);

      $ic_baseline = $baseline . DIRECTORY_SEPARATOR . Index::IGNORECONTENT;
      $ic_sut = static::$sut . DIRECTORY_SEPARATOR . Index::IGNORECONTENT;
      $ic_tmp = static::$tmp . DIRECTORY_SEPARATOR . Index::IGNORECONTENT;
      $ic_fixtures = static::$fixtures . DIRECTORY_SEPARATOR . Index::IGNORECONTENT;

      if (str_contains(static::$fixtures, DIRECTORY_SEPARATOR . static::BASELINE_DIR)) {
        fwrite(STDERR, '[INFO] Updating baseline fixtures' . PHP_EOL);
        File::copyIfExists($ic_baseline, $ic_sut);
        File::copyIfExists($ic_baseline, $ic_tmp);
        File::rmdir($baseline);
        File::sync(static::$sut, $baseline);
        static::replaceVersions($baseline);
        File::copyIfExists($ic_tmp, $ic_baseline);
        fwrite(STDERR, '[INFO] Baseline fixtures updated' . PHP_EOL);
      }
      else {
        fwrite(STDERR, '[INFO] Updating other fixtures' . PHP_EOL);
        File::copyIfExists($ic_fixtures, $ic_tmp);
        File::rmdir(static::$fixtures);
        File::diff($baseline, static::$sut, static::$fixtures);
        File::copyIfExists($ic_tmp, $ic_fixtures);
        fwrite(STDERR, '[INFO] Other fixtures updated' . PHP_EOL);
      }
    }

    parent::tearDown();
  }

  protected function runNonInteractiveInstall(?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $dst = $dst ?? static::$sut;

    if ($dst !== '' && $dst !== '0') {
      $args[InstallCommand::ARG_DESTINATION] = $dst;
    }

    $defaults = [
      InstallCommand::OPTION_NO_INTERACTION => TRUE,
      InstallCommand::OPTION_URI => File::dir(static::$root),
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

}
