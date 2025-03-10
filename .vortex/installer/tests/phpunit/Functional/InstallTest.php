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
    $this->runInstall(static::fill());

    //    // Custom welcome message.
    $this->assertTesterSuccessOutputContains('Welcome to Vortex interactive installer');

    $this->assertFixtureDirectoryEqualsSut('post_install');
    //    $this->assertFixtureDirectoryEqualsSut('post_install');
    //    $this->assertComposerLockUpToDate();
  }

}
