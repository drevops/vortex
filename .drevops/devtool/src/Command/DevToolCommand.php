<?php

namespace DrevOps\DevTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Scaffold update command.
 *
 * Allows to update scaffold files.
 */
abstract class DevToolCommand extends Command {

  /**
   * IO.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected SymfonyStyle $io;

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->io = $this->initIo($input, $output);

    $exit_code = $this->doExecute($input);

    $this->io->success('Finished ' . strtolower($this->getDescription()));

    return $exit_code;
  }

  /**
   * Execute command's business logic.
   *
   * @return int
   *   Command exit code.
   */
  abstract protected function doExecute(InputInterface $input): int;

  /**
   * Initialize IO.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @return \Symfony\Component\Console\Style\SymfonyStyle
   *   IO.
   */
  protected function initIo(InputInterface $input, OutputInterface $output): SymfonyStyle {
    // Add support for '<code>'.
    $output->getFormatter()->setStyle('code', new OutputFormatterStyle('yellow'));

    // Add support for '<bold>'.
    $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(NULL, NULL, ['bold']));

    return new SymfonyStyle($input, $output);
  }

}
