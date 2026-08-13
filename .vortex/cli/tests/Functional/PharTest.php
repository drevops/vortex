<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use DrevOps\VortexCli\Command\InstallCommand;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test PHAR cleanup functionality.
 */
#[CoversClass(InstallCommand::class)]
class PharTest extends FunctionalTestCase {

  use ProcessTrait;
  use TuiTrait;

  /**
   * The PHAR file path.
   */
  protected string $pharFile;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    static::buildPhar('.build/vortex.phar');
  }

  protected function setUp(): void {
    parent::setUp();

    // We use 'Star Wars' theme for the tests, so setting up SUT directory
    // so that the install command can gather the answers from the directory
    // name.
    static::$sut = static::locationsMkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    // Copy the PHAR file to the SUT directory.
    $this->pharFile = static::$sut . DIRECTORY_SEPARATOR . 'vortex';
    File::copy(getcwd() . '/.build/vortex.phar', $this->pharFile);

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  protected function tearDown(): void {
    $this->processTearDown();

    parent::tearDown();
  }

  public function testPhar(): void {
    $this->runInstallationWithPhar($this->pharFile);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Welcome to the Vortex CLI non-interactive install');
    $this->assertFileExists(static::$sut . DIRECTORY_SEPARATOR . 'composer.json', 'Composer file should be created after successful installation');
    $this->assertFileDoesNotExist($this->pharFile, 'PHAR file should be removed after successful installation');
  }

  public function testPharOptionNoCleanup(): void {
    $this->runInstallationWithPhar($this->pharFile, ['no-cleanup' => TRUE]);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Welcome to the Vortex CLI non-interactive install');
    $this->assertFileExists(static::$sut . DIRECTORY_SEPARATOR . 'composer.json', 'Composer file should be created after successful installation');
    $this->assertFileExists($this->pharFile, 'PHAR file should NOT be removed when --no-cleanup option is used');
  }

  public function testPharOptionHelp(): void {
    $this->runInstallationWithPhar($this->pharFile, ['help' => TRUE]);

    $this->assertProcessSuccessful();
    // A bare invocation is resolved by the target directory, so its help
    // describes that rather than any one verb.
    $this->assertProcessOutputContains('Install into a new directory, or reconfigure an existing Vortex project.');
    $this->assertProcessOutputNotContains('Welcome to the Vortex CLI non-interactive install');
    $this->assertFileDoesNotExist(static::$sut . DIRECTORY_SEPARATOR . 'composer.json', 'Composer file should NOT be created when --help flag is used');
    $this->assertFileExists($this->pharFile, 'PHAR file should NOT be removed when --help option is used');
  }

  /**
   * Every verb runs from the built PHAR.
   */
  #[DataProvider('dataProviderPharRunsVerb')]
  public function testPharRunsVerb(string $verb, string $expected): void {
    $this->processRun('php', [$this->pharFile, $verb, '--help']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains($expected);
  }

  /**
   * Data provider for testPharRunsVerb().
   *
   * @return \Iterator<string, array{string, string}>
   *   Test data.
   */
  public static function dataProviderPharRunsVerb(): \Iterator {
    yield 'install' => ['install', 'Install Vortex from remote or local repository.'];
    yield 'update' => ['update', 'Update the project to a template version, re-applying your answers.'];
    yield 'configure' => ['configure', 'Reconfigure an existing project in place.'];
    yield 'doctor' => ['doctor', 'Diagnose the local environment for common problems.'];
    yield 'build' => ['build', 'Build the site using ahoy build.'];
  }

  /**
   * The command list advertises every verb and hides the router.
   */
  public function testPharListsVerbs(): void {
    $this->processRun('php', [$this->pharFile, 'list']);

    $this->assertProcessSuccessful();

    foreach (['install', 'update', 'configure', 'doctor', 'build'] as $verb) {
      $this->assertProcessOutputContains($verb);
    }

    // The router is reached by typing nothing, never by name, so its own
    // description should not appear among the listed commands.
    $this->assertProcessOutputNotContains('Install into a new directory, or reconfigure an existing Vortex project.');
  }

  protected static function buildPhar(string $dst): void {
    fwrite(STDERR, 'Building CLI PHAR file...');
    if (!file_exists('vendor')) {
      $exit_code = 0;
      passthru('composer install --no-dev --optimize-autoloader >/dev/null 2>&1 ', $exit_code);
      if ($exit_code !== 0) {
        throw new \RuntimeException('Failed to install dependencies for PHAR build.');
      }
    }

    $exit_code = 0;
    passthru('composer build >/dev/null 2>&1', $exit_code);

    if ($exit_code !== 0) {
      throw new \RuntimeException('Failed to build PHAR file.');
    }

    fwrite(STDERR, 'done!' . PHP_EOL);
  }

  protected function runInstallationWithPhar(string $phar_path, array $options = [], array $inputs = []): void {
    $arguments = [$phar_path];

    $defaults = [
      InstallCommand::OPTION_DESTINATION => static::$sut,
      InstallCommand::OPTION_URI => File::dir(static::$root),
    ];
    $options += $defaults;

    // The interactive mode is not supported in the tests as the CLI
    // uses the form engine, which requires a real TTY or a series of fallback
    // callbacks to be defined. These callbacks are not implemented yet, so
    // we enforce the non-interactive mode for the tests.
    // @see https://github.com/drush-ops/drush/blob/13.x/src/Commands/ConfiguresPrompts.php
    $options['no-interaction'] = TRUE;

    foreach ($options as $option => $value) {
      if ($value === TRUE) {
        $arguments[] = '--' . $option;
      }
      elseif ($value !== FALSE && $value !== NULL) {
        $arguments[] = '--' . $option . '=' . $value;
      }
    }

    $this->processRun('php', $arguments, $inputs, ['VORTEX_CLI_INSTALL_IS_DEMO_DB_FETCH_SKIP' => '1'], 1200, 300);
  }

}
