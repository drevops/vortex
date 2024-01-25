<?php

namespace Drevops\Installer\Tests\Functional;

use Drevops\Installer\Tests\Traits\AssertTrait;
use Drevops\Installer\Tests\Traits\EnvTrait;
use Drevops\Installer\Tests\Traits\FixturesTrait;
use DrevOps\Installer\Utils\Env;
use PHPUnit\Framework\TestCase;

/**
 *
 */
abstract class FunctionalTestCase extends TestCase {

  use AssertTrait;
  use EnvTrait;
  use FixturesTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::vfsSetRoot();
    static::fixturesPrepare();
  }

  protected function tearDown(): void {
    parent::tearDown();

    static::envReset();
  }

  /**
   * Disable the installation run.
   */
  protected function disableInstallRun() {
    static::envSet(Env::INSTALLER_INSTALL_PROCEED, 0);
  }

}
