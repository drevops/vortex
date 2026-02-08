<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use DrevOps\Vortex\Tests\Traits\GitTrait;
use DrevOps\Vortex\Tests\Traits\HelpersTrait;
use DrevOps\Vortex\Tests\Traits\ProcessTrait;
use DrevOps\Vortex\Tests\Traits\SutTrait;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;

/**
 * Base class for all functional tests.
 */
class FunctionalTestCase extends UnitTestCase {

  use AssertArrayTrait;
  use EnvTrait;
  use GitTrait;
  use LocationsTrait;
  use ProcessTrait;
  use SutTrait;
  use HelpersTrait;

  protected function setUp(): void {
    // Initialize locations with the project root as the base directory.
    self::locationsInit(File::cwd() . '/../..');

    // We use 'Star Wars'-themed test assertions, so we need to create a named
    // SUT directory for the installer to gather the answers from the directory
    // name.
    static::$sut = static::locationsMkdir(static::$workspace . '/star_wars');

    // Export the current codebase to a fixture remote repository.
    // Any uncommitted changes will not be included, so make sure to commit
    // any changes you want to test against.
    $this->fixtureExportCodebase(static::$root, static::$repo);

    // Always show logger information.
    $this->loggerSetVerbose(TRUE);

    // Show process output based on the debug flags.
    $this->processStreamOutput = static::isDebug();

    // Setting up logger step method prefix.
    static::$loggerStepMethodPrefix = 'subtest';

    static::logSection('TEST START | ' . $this->name(), double_border: TRUE);

    chdir(static::$sut);
  }

  protected function tearDown(): void {
    static::logSection('TEST DONE | ' . $this->name(), double_border: TRUE);

    $test_failed = $this->status() instanceof Failure || $this->status() instanceof Error;

    // Collect SUT test artifacts before cleanup.
    $this->collectSutArtifacts($test_failed);

    if ($test_failed) {
      $this->logNote('Skipping cleanup as test has failed.');
      $this->log(static::locationsInfo());
    }
    elseif (static::isDebug()) {
      $this->logNote('Skipping cleanup as debug mode is on.');
      $this->log(static::locationsInfo());
    }
    else {
      // Test passed and debug mode is off → cleanup.
      $this->dockerCleanup();
      $this->processTearDown();
    }

    parent::tearDown();
  }

  protected function collectSutArtifacts(bool $test_failed): void {
    // On failure, containers are still running but syncToHost() may not have
    // been called before the failure. Try to sync artifacts from the container.
    if ($test_failed && !$this->volumesMounted()) {
      try {
        $this->syncToHost('.logs');
      }
      catch (\Throwable) {
        // Ignore - container may not be running or .logs may not exist.
      }
    }

    $sut_logs = static::$sut . '/.logs';
    if (!is_dir($sut_logs)) {
      return;
    }

    $dest = dirname(__DIR__, 2) . '/.logs/sut/' . $this->name();
    File::copy($sut_logs, $dest);

    $this->logNote('SUT test artifacts copied to: ' . $dest);
  }

  /**
   * {@inheritdoc}
   */
  public static function locationsFixturesDir(): string {
    return '.vortex/tests/phpunit/Fixtures';
  }

  public function fixtureExportCodebase(string $src, string $dst): void {
    $current_dir = File::cwd();
    if (!File::exists($dst)) {
      throw new \RuntimeException('Fixture export destination directory does not exist: ' . $dst);
    }
    chdir($src);
    shell_exec(sprintf('git archive --format=tar HEAD | (cd %s && tar -xf -)', escapeshellarg($dst)));
    chdir($current_dir);
  }

  /**
   * {@inheritdoc}
   */
  public static function isDebug(): bool {
    return !empty(getenv('TEST_VORTEX_DEBUG')) || parent::isDebug();
  }

  /**
   * {@inheritdoc}
   */
  public function ignoredPaths(): array {
    return [
      '.7z',
      '.avif',
      '.bz2',
      '.gz',
      '.heic',
      '.heif',
      '.pdf',
      '.rar',
      '.tar',
      '.woff',
      '.woff2',
      '.xz',
      '.zip',
      '.bmp',
      '.gif',
      '.ico',
      '.jpeg',
      '.jpg',
      '.png',
      '.svg',
      '.svgz',
      '.tif',
      '.tiff',
      '.webp',
      '/core/',
      '/libraries/',
      '/modules/contrib/',
      'modules.README.txt',
      'modules/README.txt',
      '/themes/contrib/',
      'themes.README.txt',
      'themes/README.txt',
    ];
  }

  public function dockerCleanup(): void {
    shell_exec('docker compose -p star_wars down --remove-orphans --volumes --timeout 1 > /dev/null 2>&1');
  }

}
