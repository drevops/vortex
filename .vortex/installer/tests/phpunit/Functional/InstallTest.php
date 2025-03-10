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
  public function testInstallDefaults(): void {
    $this->runInteractiveInstall(static::fill());

    $this->assertTesterSuccessOutputContains('Welcome to Vortex interactive installer');

    $this->assertFixtureDirectoryEqualsSut('post_install');

    $this->assertDirectoriesEqual(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
  }

}
