<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use DrevOps\Vortex\Tests\Traits\AssertFilesTrait;
use DrevOps\Vortex\Tests\Traits\GitTrait;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepBuildTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepDownloadDbTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepPrepareSutTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepTestBddAllTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepTestBddTrait;
use Symfony\Component\Process\Process;

/**
 * Base class for functional tests.
 */
class FunctionalTestCase extends UnitTestCase {

  use AssertArrayTrait;
  use AssertFilesTrait;
  use EnvTrait;
  use GitTrait;
  use LocationsTrait;
  use LoggerTrait;
  use ProcessTrait;
  use StepBuildTrait;
  use StepDownloadDbTrait;
  use StepPrepareSutTrait;
  use StepTestBddAllTrait;
  use StepTestBddTrait;

  protected function setUp(): void {
    self::locationsInit(File::cwd() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

    // We use 'Star Wars' theme for the tests, so setting up SUT directory
    // so that the installer can gather the answers from the directory name.
    static::$sut = static::locationsMkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    $this->fixtureExportCodebase(static::$root, static::$repo);

    $is_verbose = !empty(getenv('TEST_VORTEX_DEBUG')) || static::isDebug();
    $this->processStreamOutput = $is_verbose;
    $this->loggerSetVerbose(TRUE);
  }

  protected function tearDown(): void {
    $cmd = 'docker compose -p star_wars down --remove-orphans --volumes --timeout 1 > /dev/null 2>&1';
    shell_exec($cmd);

    parent::tearDown();

    $this->processTearDown();
  }

  public function fixtureExportCodebase(string $src, string $dst): void {
    $current_dir = File::cwd();
    chdir($src);
    shell_exec(sprintf('git archive --format=tar HEAD | (cd %s && tar -xf -)', escapeshellarg($dst)));
    chdir($current_dir);
  }

  public function syncToHost(): void {
    if ($this->volumesMounted()) {
      return;
    }

    $this->logSubstep('Syncing files from container to host');
    shell_exec('docker compose cp -L cli:/app/. .');
  }

  public function syncToContainer(): void {
    if ($this->volumesMounted()) {
      return;
    }

    $this->logSubstep('Syncing files from host to container');
    shell_exec('docker compose cp -L . cli:/app/');
  }

  public function processRunInContainer(
    string $command,
    array $arguments = [],
    array $inputs = [],
    array $env = [],
    int $timeout = 60,
    int $idle_timeout = 30,
  ): Process {
    return $this->processRun('ahoy cli ' . $command, $arguments, $inputs, $env, $timeout, $idle_timeout);
  }

  public function volumesMounted(): bool {
    return getenv('VORTEX_DEV_VOLUMES_SKIP_MOUNT') != 1;
  }

  protected function assertFilesExist(string $directory, array $files): void {
    foreach ($files as $file) {
      $this->assertFileExists($directory . DIRECTORY_SEPARATOR . $file);
    }
  }

}
