<?php

namespace Drevops\Installer\Tests\Functional\Application;

use Drevops\Installer\Tests\Functional\ApplicationTestCase;

class OptionsApplicationTest extends ApplicationTestCase {

  /**
   * Test the execute method.
   *
   * @covers        \DrevOps\Installer\InstallerApp::__construct
   * @covers        \DrevOps\Installer\Command\InstallCommand::configure
   * @covers        \DrevOps\Installer\Command\InstallCommand::initIo
   * @dataProvider  dataProviderExecuteOptions
   * @runInSeparateProcess
   */
  public function testExecuteOptions($input, $expected) {
    $this->disableInstallRun();
    $this->assertStringContainsString($expected, $this->execute($input));
  }

  public static function dataProviderExecuteOptions() {
    return [
      [['--help' => TRUE], 'Destination directory. Optional. Defaults to the current directory'],
      [['--version' => TRUE], 'DrevOps CLI Installer @git-version@'],
    ];
  }

}
