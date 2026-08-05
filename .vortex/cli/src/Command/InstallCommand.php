<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Install command.
 *
 * Installs Vortex from a remote or local repository into the destination.
 *
 * @package DrevOps\VortexCli\Command
 */
class InstallCommand extends AbstractInstallCommand {

  /**
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'install';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('install');
    $this->setDescription('Install Vortex from remote or local repository.');
    $this->setHelp(<<<EOF
  <info>Interactively install Vortex from the latest stable release into the current directory:</info>
  php vortex.phar install --destination=.

  <info>Non-interactively install Vortex from the latest stable release into the specified directory:</info>
  php vortex.phar install --no-interaction --destination=path/to/destination

  <info>Install from the latest auto-discovered stable release (default behavior if --uri is specified):</info>
  php vortex.phar install --uri=https://github.com/drevops/vortex.git
  php vortex.phar install --uri=https://github.com/drevops/vortex.git#stable

  <info>Install using repository URL with specific git ref after #:</info>
  php vortex.phar install --uri=https://github.com/drevops/vortex.git#25.11.0
  php vortex.phar install --uri=https://github.com/drevops/vortex.git#v1.2.3
  php vortex.phar install --uri=https://github.com/drevops/vortex.git#main

  <info>Copy GitHub URL directly from your browser:</info>
  php vortex.phar install --uri=https://github.com/drevops/vortex/releases/tag/25.11.0
  php vortex.phar install --uri=https://github.com/drevops/vortex/tree/1.2.3
  php vortex.phar install --uri=https://github.com/drevops/vortex/tree/main
  php vortex.phar install --uri=https://github.com/drevops/vortex/commit/abcd123
EOF
    );
    $this->addCommonOptions();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    return $this->doInstall($input, $output);
  }

}
