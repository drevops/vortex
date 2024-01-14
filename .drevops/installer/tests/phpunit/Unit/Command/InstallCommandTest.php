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
 * @coversDefaultClass \DrevOps\Installer\Command\InstallCommand
 */
class InstallCommandTest extends TestCase {

  /**
   * Test the execute method.
   *
   * @covers ::execute
   */
  public function testExecute(): void {
    $application = new Application();
    $application->add(new InstallCommand());

    $command = $application->find('DrevOps CLI installer');
    $command_tester = new CommandTester($command);

    $command_tester->execute(['--help' => NULL], [
      'interactive' => FALSE,
      'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
      'capture_stderr_separately' => FALSE,
    ]);

    // The output of the command in the console.
    $output = $command_tester->getDisplay();
    $this->assertStringContainsString('php install destination', $output);
  }

}
