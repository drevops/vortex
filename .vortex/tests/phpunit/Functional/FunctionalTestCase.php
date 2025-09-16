<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use DrevOps\Vortex\Tests\Traits\AssertProjectFilesTrait;
use DrevOps\Vortex\Tests\Traits\GitTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepBuildTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepDatabaseTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepPrepareSutTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepTestTrait;
use Symfony\Component\Process\Process;

/**
 * Base class for functional tests.
 */
class FunctionalTestCase extends UnitTestCase {

  /**
   * URL to the test demo database.
   *
   * Tests use demo database and 'ahoy download-db' command, so we need
   * to set the CURL DB to test DB.
   */
  const VORTEX_INSTALLER_DEMO_DB_TEST = 'https://github.com/drevops/vortex/releases/download/25.4.0/db_d11_2.test.sql';

  use AssertArrayTrait;
  use AssertProjectFilesTrait;
  use EnvTrait;
  use GitTrait;
  use LocationsTrait;
  use LoggerTrait;
  use ProcessTrait {
    ProcessTrait::processRun as traitProcessRun;
  }
  use StepBuildTrait;
  use StepPrepareSutTrait;
  use StepTestTrait;
  use StepDatabaseTrait;

  protected function setUp(): void {
    self::locationsInit(File::cwd() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

    // We use 'Star Wars' theme for the tests, so setting up SUT directory
    // so that the installer can gather the answers from the directory name.
    static::$sut = static::locationsMkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    $this->fixtureExportCodebase(static::$root, static::$repo);

    $is_verbose = !empty(getenv('TEST_VORTEX_DEBUG')) || static::isDebug();
    $this->processStreamOutput = $is_verbose;
    $this->loggerSetVerbose(TRUE);

    static::logSection('TEST START | ' . $this->name(), double_border: TRUE);
    $this->logNote(static::locationsInfo());

    chdir(static::$sut);

    $this->stepPrepareSut();
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

  /**
   * {@inheritdoc}
   */
  public function processRun(
    string $command,
    array $arguments = [],
    array $inputs = [],
    array $env = [],
    int $timeout = 60,
    int $idle_timeout = 60,
  ): Process {
    $env += [
      'AHOY_CONFIRM_RESPONSE' => 'y',
      'AHOY_CONFIRM_WAIT_SKIP' => 1,
    ];

    return $this->traitProcessRun($command, $arguments, $inputs, $env, $timeout, $idle_timeout);
  }

  public function cmd(
    string $cmd,
    array|string|null $out = NULL,
    ?string $txt = NULL,
    array $arg = [],
    array $inp = [],
    array $env = [],
    int $tio = 120,
    int $ito = 60,
  ): ?Process {
    if ($txt) {
      $this->logNote($txt);
    }

    $this->processRun($cmd, $arg, $inp, $env, $tio, $ito);
    $this->assertProcessSuccessful();

    if ($out) {
      $this->assertProcessAnyOutputContainsOrNot($out);
    }

    return $this->process;
  }

  public function cmdFail(
    string $cmd,
    array|string|null $out = NULL,
    ?string $txt = NULL,
    array $arg = [],
    array $inp = [],
    array $env = [],
    int $tio = 60,
    int $ito = 60,
  ): ?Process {
    if ($txt) {
      $this->logNote($txt);
    }

    $this->processRun($cmd, $arg, $inp, $env, $tio, $ito);
    $this->assertProcessFailed();

    if ($out) {
      $this->assertProcessAnyOutputContainsOrNot($out);
    }

    return $this->process;
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

  public function volumesMounted(): bool {
    return getenv('VORTEX_DEV_VOLUMES_SKIP_MOUNT') != 1;
  }

  protected function trimFile(string $file): void {
    $content = File::read($file);
    $lines = explode("\n", $content);
    // Remove last line.
    array_pop($lines);
    File::dump($file, implode("\n", $lines));
  }

  protected function stepWarmCaches(): void {
    $this->logSubstep('Warming up caches');
    $this->cmd('ahoy drush cr');
    $this->cmd('ahoy cli curl -- -sSL -o /dev/null -w "%{http_code}" http://nginx:8080 | grep -q 200');
  }

  protected function addVarToFile(string $file, string $var, string $value): void {
    // Backup original file first.
    $this->backupFile($file);
    $content = File::read($file);
    $content .= sprintf('%s%s=%s%s', PHP_EOL, $var, $value, PHP_EOL);
    File::dump($file, $content);
  }

  protected function backupFile(string $file): void {
    $backup_dir = '/tmp/bkp';
    if (!is_dir($backup_dir)) {
      mkdir($backup_dir, 0755, TRUE);
    }
    File::copy($file, $backup_dir . '/' . basename($file));
  }

  protected function restoreFile(string $file): void {
    $backup_file = '/tmp/bkp/' . basename($file);
    if (file_exists($backup_file)) {
      File::copy($backup_file, $file);
    }
  }

  protected function createDevelopmentSettings(string $webroot = 'web'): void {
    File::copy($webroot . '/sites/default/example.settings.local.php', $webroot . '/sites/default/settings.local.php');
    // Assert manually created local settings file exists.
    $this->assertFileExists($webroot . '/sites/default/settings.local.php');

    File::copy($webroot . '/sites/default/example.services.local.yml', $webroot . '/sites/default/services.local.yml');
    // Assert manually created local services file exists.
    $this->assertFileExists($webroot . '/sites/default/services.local.yml');
  }

  protected function removeDevelopmentSettings(string $webroot = 'web'): void {
    File::remove([
      $webroot . '/sites/default/settings.local.php',
      $webroot . '/sites/default/services.local.yml',
    ]);
    $this->assertFileDoesNotExist($webroot . '/sites/default/settings.local.php');
    $this->assertFileDoesNotExist($webroot . '/sites/default/services.local.yml');
  }

  protected function assertFilesPresent(string $webroot): void {
    // Use existing method from base class with correct signature.
    $this->assertCommonFilesPresent($webroot);
  }

  protected function assertGitRepo(): void {
    // @todp Use gitAssertIsRepository().
    $this->assertDirectoryExists('.git');
  }

  protected function substepDownloadDb(bool $copy_to_container = FALSE): void {
    $this->logStepStart();

    File::remove('.data/db.sql');
    $this->assertFileDoesNotExist('.data/db.sql', 'File .data/db.sql should not exist before downloading the database.');

    $this->cmd(
      './scripts/vortex/download-db.sh',
      txt: 'Download demo database from ' . static::VORTEX_INSTALLER_DEMO_DB_TEST,
      env: ['VORTEX_DB_DOWNLOAD_URL' => static::VORTEX_INSTALLER_DEMO_DB_TEST],
    );

    $this->assertFileExists('.data/db.sql', 'File .data/db.sql should exist after downloading the database.');

    if ($copy_to_container && !$this->volumesMounted() && file_exists('.data/db.sql')) {
      $this->logNote('Copy database file to container');
      $this->cmd('docker compose exec -T cli mkdir -p .data', txt: 'Create .data directory in the container');
      $this->cmd('docker compose cp -L .data/db.sql cli:/app/.data/db.sql', txt: 'Copy database dump into container');
    }

    $this->logStepFinish();
  }

}
