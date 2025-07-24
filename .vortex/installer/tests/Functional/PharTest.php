<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;

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

    static::buildPhar('build/installer');
  }

  protected function setUp(): void {
    parent::setUp();

    // We use 'Star Wars' theme for the tests, so setting up SUT directory
    // so that the installer can gather the answers from the directory name.
    static::$sut = static::locationsMkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    // Copy the PHAR file to the SUT directory.
    $this->pharFile = static::$sut . DIRECTORY_SEPARATOR . 'installer';
    File::copy(getcwd() . '/build/installer', $this->pharFile);

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
    $this->assertProcessOutputContains('Welcome to Vortex non-interactive installer');
    $this->assertFileExists(static::$sut . DIRECTORY_SEPARATOR . 'composer.json', 'Composer file should be created after successful installation');
    $this->assertFileDoesNotExist($this->pharFile, 'PHAR file should be removed after successful installation');
  }

  public function testPharOptionNoCleanup(): void {
    $this->runInstallationWithPhar($this->pharFile, ['no-cleanup' => TRUE]);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Welcome to Vortex non-interactive installer');
    $this->assertFileExists(static::$sut . DIRECTORY_SEPARATOR . 'composer.json', 'Composer file should be created after successful installation');
    $this->assertFileExists($this->pharFile, 'PHAR file should NOT be removed when --no-cleanup option is used');
  }

  public function testPharOptionHelp(): void {
    $this->runInstallationWithPhar($this->pharFile, ['help' => TRUE]);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Vortex CLI installer');
    $this->assertProcessOutputNotContains('Welcome to Vortex non-interactive installer');
    $this->assertFileDoesNotExist(static::$sut . DIRECTORY_SEPARATOR . 'composer.json', 'Composer file should NOT be created when --help flag is used');
    $this->assertFileExists($this->pharFile, 'PHAR file should NOT be removed when --help option is used');
  }

  protected static function buildPhar(string $dst): void {
    fwrite(STDERR, 'Building installer PHAR file...');
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
    $arguments = [$phar_path, static::$sut];

    $defaults = [
      'uri' => File::dir(static::$root),
    ];
    $options += $defaults;

    // The interactive mode is not supported in the tests as installer
    // uses Laravel\Prompts which require a real TTY or a series of fallback
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

    $this->processRun('php', $arguments, $inputs, ['VORTEX_INSTALL_IS_DEMO_DB_DOWNLOAD_SKIP' => '1'], 1200, 300);
  }

}
