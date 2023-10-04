<?php

namespace Drevops\Installer\Tests\Functional\Command;

use Drevops\Installer\Tests\Functional\CommandTestCase;

/**
 * Class RunCommandTest.
 *
 * This is a unit test for the RunCommand class.
 *
 * @package DrevOps\Installer\Tests\Command
 */
class OptionsCommandTest extends CommandTestCase {

  /**
   * Test the execute method.
   *
   * @covers        \DrevOps\Installer\Bag\Config::isQuiet
   * @covers        \DrevOps\Installer\PrintManager::__construct
   * @covers        \DrevOps\Installer\PrintManager::printHeader
   * @covers        \DrevOps\Installer\PrintManager::printHeaderQuiet
   * @covers        \DrevOps\Installer\PrintManager::printHeaderInteractive
   * @covers        \DrevOps\Installer\PrintManager::printSummary
   * @covers        \DrevOps\Installer\PrintManager::printAbort
   * @covers        \DrevOps\Installer\Command\InstallCommand::askShouldProceed
   * @covers        \DrevOps\Installer\Command\InstallCommand::doExecute
   * @covers        \DrevOps\Installer\Command\InstallCommand::execute
   * @covers        \DrevOps\Installer\Command\InstallCommand::askQuestions
   * @covers        \DrevOps\Installer\Command\InstallCommand::initIo
   *
   * @dataProvider  dataProviderExecuteOptions
   * @runInSeparateProcess
   */
  public function testExecuteOptions($input, ...$expected) {
    static::envFromInput($input, 'DREVOPS_INSTALLER_');

    $this->disableInstallRun();
    $expected[] = 'INSTALLATION ABORTED';

    $output = $this->execute($input);
    $this->assertStringContains($output, ...$expected);
  }

  public static function dataProviderExecuteOptions() {
    return [
      [
        [],
        'WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- WELCOME TO DREVOPS QUIET INSTALLER',
        'This will install the latest version of DrevOps into your project',
        '- This will install DrevOps into your project at commit',
        '- It looks like DrevOps is already installed into this project.',
        'Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['DREVOPS_INSTALLER_COMMIT' => '1234567890'],
        'WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- WELCOME TO DREVOPS QUIET INSTALLER',
        '- This will install the latest version of DrevOps into your project',
        'This will install DrevOps into your project at commit "1234567890".',
        '- It looks like DrevOps is already installed into this project.',
        'Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['path' => static::$fixtureDstDirs['empty']],
        'WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- WELCOME TO DREVOPS QUIET INSTALLER',
        'This will install the latest version of DrevOps into your project.',
        '- This will install DrevOps into your project at commit',
        '- It looks like DrevOps is already installed into this project.',
        'Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['path' => static::$fixtureDstDirs['empty'], 'DREVOPS_INSTALLER_COMMIT' => '1234567890'],
        'WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- WELCOME TO DREVOPS QUIET INSTALLER',
        '- This will install the latest version of DrevOps into your project.',
        'This will install DrevOps into your project at commit "1234567890".',
        '- It looks like DrevOps is already installed into this project.',
        'Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['path' => static::$fixtureDstDirs['installed']],
        'WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- WELCOME TO DREVOPS QUIET INSTALLER',
        'This will install the latest version of DrevOps into your project.',
        '- This will install DrevOps into your project at commit',
        'It looks like DrevOps is already installed into this project.',
        'Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['path' => static::$fixtureDstDirs['installed'], 'DREVOPS_INSTALLER_COMMIT' => '1234567890'],
        '- WELCOME TO DREVOPS QUIET INSTALLER',
        'WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- This will install the latest version of DrevOps into your project.',
        'This will install DrevOps into your project at commit "1234567890".',
        'It looks like DrevOps is already installed into this project.',
        'Please answer the questions below to install configuration relevant to your site.',
      ],


      [
        ['--quiet' => TRUE],
        '- WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        'WELCOME TO DREVOPS QUIET INSTALLER',
        'This will install the latest version of DrevOps into your project',
        '- This will install DrevOps into your project at commit',
        '- It looks like DrevOps is already installed into this project.',
        '- Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['--quiet' => TRUE, 'DREVOPS_INSTALLER_COMMIT' => '1234567890'],
        '- WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        'WELCOME TO DREVOPS QUIET INSTALLER',
        '- This will install the latest version of DrevOps into your project',
        'This will install DrevOps into your project at commit "1234567890".',
        '- It looks like DrevOps is already installed into this project.',
        '- Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['--quiet' => TRUE, 'path' => static::$fixtureDstDirs['empty']],
        '- WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        'WELCOME TO DREVOPS QUIET INSTALLER',
        'This will install the latest version of DrevOps into your project.',
        '- This will install DrevOps into your project at commit',
        '- It looks like DrevOps is already installed into this project.',
        '- Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['--quiet' => TRUE, 'path' => static::$fixtureDstDirs['empty'], 'DREVOPS_INSTALLER_COMMIT' => '1234567890'],
        '- WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        'WELCOME TO DREVOPS QUIET INSTALLER',
        '- This will install the latest version of DrevOps into your project.',
        'This will install DrevOps into your project at commit "1234567890".',
        '- It looks like DrevOps is already installed into this project.',
        '- Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['--quiet' => TRUE, 'path' => static::$fixtureDstDirs['installed']],
        '- WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        'WELCOME TO DREVOPS QUIET INSTALLER',
        'This will install the latest version of DrevOps into your project.',
        '- This will install DrevOps into your project at commit',
        'It looks like DrevOps is already installed into this project.',
        '- Please answer the questions below to install configuration relevant to your site.',
      ],
      [
        ['--quiet' => TRUE, 'path' => static::$fixtureDstDirs['installed'], 'DREVOPS_INSTALLER_COMMIT' => '1234567890'],
        'WELCOME TO DREVOPS QUIET INSTALLER',
        '- WELCOME TO DREVOPS INTERACTIVE INSTALLER',
        '- This will install the latest version of DrevOps into your project.',
        'This will install DrevOps into your project at commit "1234567890".',
        'It looks like DrevOps is already installed into this project.',
        '- Please answer the questions below to install configuration relevant to your site.',
      ],
    ];
  }

}
