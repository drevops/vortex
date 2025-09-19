<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use DrevOps\Vortex\Tests\Traits\GitTrait;
use DrevOps\Vortex\Tests\Traits\ProcessTrait;
use DrevOps\Vortex\Tests\Traits\SutTrait;

/**
 * Base class for functional tests.
 */
class FunctionalTestCase extends UnitTestCase {

  use AssertArrayTrait;
  use EnvTrait;
  use GitTrait;
  use LocationsTrait;
  use ProcessTrait;
  use SutTrait;

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

    $this->prepareSut();
  }

  protected function tearDown(): void {
    static::logSection('TEST DONE | ' . $this->name(), double_border: TRUE);

    if ($this->tearDownShouldCleanup()) {
      // Deliberately using shell_exec to avoid issues with nested process
      // handling in the ProcessTrait.
      $this->dockerCleanup();

      $this->processTearDown();
    }
    else {
      $this->logNote('Skipping cleanup as test has failed or debug mode is on.');
      $this->log(static::locationsInfo());
    }

    parent::tearDown();
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

  public function dockerCleanup(): void {
    shell_exec('docker compose -p star_wars down --remove-orphans --volumes --timeout 1 > /dev/null 2>&1');
  }

}
