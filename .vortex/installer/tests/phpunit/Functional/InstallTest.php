<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Functional;

/**
 * Test the initial installation.
 *
 * @coversDefaultClass \DrevOps\Installer\Command\InstallCommand
 */
class InstallTest extends FunctionalTestBase {

  /**
   * Test the initial installation.
   *
   * @runInSeparateProcess
   * @group install
   *
   * @covers ::execute
   */
  public function testInstallInteractiveDefaults(): void {
    $this->runInteractiveInstall(static::fill());

    $this->assertTesterSuccessOutputContains('Welcome to Vortex interactive installer');

    $this->assertCommon();
  }

  /**
   * Test the initial installation.
   *
   * @runInSeparateProcess
   * @group install
   *
   * @covers ::execute
   */
  public function testInstallNonInteractiveDefaults(): void {
    $this->runNonInteractiveInstall();

    $this->assertTesterSuccessOutputContains('Welcome to Vortex non-interactive installer');

    $this->assertCommon();
  }

  protected function assertCommon() {
    $this->assertFixtureDirectoryEqualsSut('post_install');

    $this->assertDirectoriesEqual(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
  }

}
