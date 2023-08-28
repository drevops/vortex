<?php

namespace DrevOps\Installer\Tests\Command;

use DrevOps\Installer\Command\InstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RunCommandTest.
 *
 * This is a unit test for the RunCommand class.
 *
 * @package DrevOps\Installer\Tests\Command
 */
class InstallCommandTest extends TestCase {

  /**
   * Test the execute method.
   */
  public function testExecute() {
    $application = new Application();
    $application->add(new InstallCommand());

    $command = $application->find('install');
    $command_tester = new CommandTester($command);

    $command_tester->execute(['--help' => NULL], [
      'interactive' => FALSE,
      'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
      'capture_stderr_separately' => FALSE,
    ]);

    // The output of the command in the console.
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString('DrevOps Installer', $output);
  }

}
