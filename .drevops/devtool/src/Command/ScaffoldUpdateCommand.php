<?php

namespace DrevOps\DevTool\Command;

use Composer\Console\Input\InputOption;
use DrevOps\DevTool\Scaffold\ScaffoldManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Scaffold update command.
 *
 * Allows to update scaffold files.
 */
class ScaffoldUpdateCommand extends DevToolCommand {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'scaffold:update';

  /**
   * Project root.
   *
   * @var string
   */
  protected string $root;

  /**
   * Scaffold manager.
   *
   * @var \DrevOps\DevTool\Scaffold\ScaffoldManager
   */
  protected ScaffoldManager $scaffoldManager;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setDescription('Update scaffold files')
      ->addOption('root', 'r', InputOption::VALUE_REQUIRED, 'Path to the root of the project', getcwd());
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(InputInterface $input): int {
    $root = $input->getOption('root');
    if (!is_string($root)) {
      throw new \InvalidArgumentException('Root must be a string');
    }
    $this->root = $root;

    $this->io->write('Updating <code>composer.json</code>... ');
    (new ScaffoldManager($this->root))->update();
    $this->io->write('<bold>OK</bold>');

    return Command::SUCCESS;
  }

}
