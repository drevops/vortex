<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Functional;

use DrevOps\Installer\Command\InstallCommand;
use DrevOps\Installer\Tests\Traits\ApplicationTrait;
use DrevOps\Installer\Tests\Traits\TuiTrait;
use DrevOps\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Env;

/**
 * Base class for functional tests.
 */
abstract class FunctionalTestBase extends UnitTestBase {

  use ApplicationTrait;
  use TuiTrait;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    static::tuiSetUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::$tester = static::applicationInit(InstallCommand::class);

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::tuiTearDown();

    parent::tearDown();
  }

  protected static function runNonInteractiveInstall(?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $dst = $dst ?? static::$sut;

    if ($dst !== '' && $dst !== '0') {
      $args[InstallCommand::ARG_DESTINATION] = $dst;
    }

    $defaults = [
      InstallCommand::OPTION_NO_ITERACTION => TRUE,
      InstallCommand::OPTION_URI => static::$root,
    ];
    $options += $defaults;

    foreach ($options as $option => $value) {
      $args['--' . $option] = $value;
    }

    Env::put(Config::DEMO_MODE_SKIP, '1');

    static::applicationRun($args, [], $expect_fail);
  }

  protected static function runInteractiveInstall(array $answers = [], ?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    static::tuiInput($answers);
    static::runNonInteractiveInstall($dst, $options + [InstallCommand::OPTION_NO_ITERACTION => FALSE], $expect_fail);
  }

  protected function assertSutContains(string $needle): void {
    $this->assertDirectoryContainsWord($needle, static::$sut, [
      'scripts/vortex',
    ]);
  }

  protected function assertSutNotContains(string $needle): void {
    $this->assertDirectoryNotContainsWord($needle, static::$sut, [
      'scripts/vortex',
    ]);
  }

}
