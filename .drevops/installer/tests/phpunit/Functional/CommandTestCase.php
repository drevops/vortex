<?php

namespace Drevops\Installer\Tests\Functional;

use DrevOps\Installer\Command\InstallCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends FunctionalTestCase {

  /**
   * @var \Symfony\Component\Console\Tester\CommandTester
   */
  protected $tester;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $application = new Application();
    $command = new InstallCommand();
    $application->add($command);
    $application->setDefaultCommand($command->getName(), TRUE);

    $command = $application->find('install');
    $this->tester = new CommandTester($command);
  }

  /**
   * Run the application.
   *
   * @param array $input
   *   The input.
   * @param array $options
   *   The options.
   *
   * @return string
   *   The output.
   */
  protected function execute(array $input, $options = []): string {
    $this->tester->execute($input, $options + [
        'interactive' => FALSE,
      ]);

    return $this->tester->getDisplay();
  }

}
