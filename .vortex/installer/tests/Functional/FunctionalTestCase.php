<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait as UpstreamTuiTrait;
use DrevOps\Installer\Command\InstallCommand;
use DrevOps\Installer\Tests\Traits\TuiTrait;
use DrevOps\Installer\Tests\Unit\UnitTestCase;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

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
  protected function setUp(): void {
    parent::setUp();

    static::applicationInitFromCommand(InstallCommand::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::tuiTearDown();

    parent::tearDown();
  }

  protected function runNonInteractiveInstall(?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $dst = $dst ?? static::$sut;

    if ($dst !== '' && $dst !== '0') {
      $args[InstallCommand::ARG_DESTINATION] = $dst;
    }

    $defaults = [
      InstallCommand::OPTION_NO_ITERACTION => TRUE,
      InstallCommand::OPTION_URI => File::dir(static::$root),
    ];
    $options += $defaults;

    foreach ($options as $option => $value) {
      $args['--' . $option] = $value;
    }

    Env::put(Config::DEMO_MODE_SKIP, '1');

    $this->applicationRun($args, [], $expect_fail);
  }

  protected function runInteractiveInstall(array $answers = [], ?string $dst = NULL, array $options = [], bool $expect_fail = FALSE): void {
    $this->runNonInteractiveInstall($dst, $options + [InstallCommand::OPTION_NO_ITERACTION => FALSE], $expect_fail);
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
