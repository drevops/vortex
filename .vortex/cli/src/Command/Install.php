<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs Vortex: downloads the template, customizes it and copies it out.
 *
 * @package DrevOps\VortexCli\Command
 */
class Install extends AbstractInstallCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('install')
      ->setDescription('Install Vortex from a remote or local repository.');

    $this->addCommonOptions();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    return $this->doInstall($input, $output);
  }

}
