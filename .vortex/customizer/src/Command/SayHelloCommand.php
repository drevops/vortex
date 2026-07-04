<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Say hello command.
 *
 * Allows to say hello.
 *
 * @package DrevOps\Customizer\Command
 */
class SayHelloCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('say-hello')
      ->setDescription('Says hello')
      ->setHelp('This command allows you to say hello...');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Hello, Symfony console!');

    return Command::SUCCESS;
  }

}
