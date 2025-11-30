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
 * Run with `UPDATE_FIXTURES=1` to update test fixtures.
 */
abstract class AbstractHandlerProcessTestCase extends FunctionalTestCase {

  /**
   * Override options to pass to the install command.
   */
  public array $installOptions = [];

  protected function setUp(): void {
    parent::setUp();

    static::applicationInitFromCommand(InstallCommand::class);

    // Use a two-words name for the sut directory.
    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  #[DataProvider('dataProviderInstall')]
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

    $this->runNonInteractiveInstall(options: $this->installOptions);

    $expected = empty($expected) ? ['Welcome to the Vortex non-interactive installer'] : $expected;
    $this->assertApplicationOutputContains($expected);

    $baseline = File::dir(static::$fixtures . '/../' . self::BASELINE_DIR);
    static::replaceVersions(static::$sut);
    $this->assertDirectoryEqualsPatchedBaseline(static::$sut, $baseline, static::$fixtures);

    $this->assertCommon();

    if ($after instanceof SerializableClosure) {
      $after = static::cu($after);
      $after($this);
    }
  }

  abstract public static function dataProviderInstall(): array;

  protected function assertCommon(): void {
    $this->assertDirectoryEqualsDirectory(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    if (file_exists(static::$root . '/scripts/vortex.yml')) {
      $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
    }

    $this->assertYamlFileIsValid('.ahoy.yml');
    $this->assertJsonFileIsValid('composer.json');
  }

  protected static function defaultAnswers(): array {
    return [
      'namespace' => 'YodasHut',
      'project' => 'force-crystal',
      'author' => 'Luke Skywalker',
      'use_php' => static::TUI_DEFAULT,
      'use_php_command' => static::TUI_DEFAULT,
      'php_command_name' => static::TUI_DEFAULT,
      'use_php_command_build' => static::TUI_DEFAULT,
      'use_php_script' => static::TUI_DEFAULT,
      'use_nodejs' => static::TUI_DEFAULT,
      'use_shell' => static::TUI_DEFAULT,
      'use_release_drafter' => static::TUI_DEFAULT,
      'use_pr_autoassign' => static::TUI_DEFAULT,
      'use_funding' => static::TUI_DEFAULT,
      'use_pr_template' => static::TUI_DEFAULT,
      'use_renovate' => static::TUI_DEFAULT,
      'use_docs' => static::TUI_DEFAULT,
      'remove_self' => static::TUI_DEFAULT,
    ];
  }

}
