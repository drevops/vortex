<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Abstract base class for installer tests.
 *
 * Provides common test logic for all installer test scenarios.
 * Run with `UPDATE_SNAPSHOTS=1` to update test snapshots.
 */
abstract class AbstractHandlerProcessTestCase extends FunctionalTestCase {

  /**
   * Override options to pass to the install command.
   */
  public array $installOptions = [];

  /**
   * Prompt overrides to pass via --prompts option.
   *
   * Keyed by handler ID. Set in $before closures.
   *
   * @var array<string, mixed>
   */
  public array $prompts = [];

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_');
    static::envUnsetPrefix('DRUPAL_');
    static::envUnsetPrefix('LAGOON_');
    static::envUnset('WEBROOT');
    static::envUnset('TZ');

    static::applicationInitFromCommand(InstallCommand::class);

    // Use a two-words name for the sut directory.
    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  #[DataProvider('dataProviderHandlerProcess')]
  #[RunInSeparateProcess]
  public function testHandlerProcess(
    ?SerializableClosure $before = NULL,
    ?SerializableClosure $after = NULL,
    array $expected = [],
  ): void {
    static::$fixtures = static::locationsFixtureDir();

    if ($before instanceof SerializableClosure) {
      $before = static::cu($before);
      $before($this);
    }

    if (!empty($this->prompts)) {
      $this->installOptions[InstallCommand::OPTION_PROMPTS] = json_encode($this->prompts);
    }

    $this->runNonInteractiveInstall(options: $this->installOptions);

    $expected = empty($expected) ? ['Welcome to the Vortex non-interactive installer'] : $expected;
    $this->assertApplicationOutputContains($expected);

    $baseline = File::dir(static::$fixtures . '/../' . self::BASELINE_DIR);
    $this->replaceVersions(static::$sut);
    $this->assertSnapshotMatchesBaseline(static::$sut, $baseline, static::$fixtures);

    $this->assertCommon();

    if ($after instanceof SerializableClosure) {
      $after = static::cu($after);
      $after($this);
    }
  }

  abstract public static function dataProviderHandlerProcess(): \Iterator;

  protected function assertCommon(): void {
    $this->assertDirectoriesIdentical(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    if (file_exists(static::$root . '/scripts/vortex.yml')) {
      $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
    }

    $this->assertYamlFileIsValid('.ahoy.yml');
    $this->assertJsonFileIsValid('composer.json');
  }

}
