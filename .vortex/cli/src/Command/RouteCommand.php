<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Utils\Project;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Route command.
 *
 * The default command: a bare invocation is resolved by the state of the target
 * directory. Every option is passed through untouched, so whichever verb is
 * selected sees exactly what was typed.
 *
 * @package DrevOps\VortexCli\Command
 */
class RouteCommand extends Command {

  /**
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'route';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('route');
    $this->setDescription('Install into a new directory, or reconfigure an existing Vortex project.');
    $this->setHelp(<<<EOF
  Running the CLI without a command resolves by the state of the target
  directory: an existing Vortex project is reconfigured, anything else gets a
  fresh install.

  <info>Install into the current directory:</info>
  php vortex.phar

  <info>Reconfigure the Vortex project in the current directory:</info>
  php vortex.phar

  <info>Describe the available questions, whichever verb applies:</info>
  php vortex.phar --schema
  php vortex.phar --agent-help

  <info>List every command and its options:</info>
  php vortex.phar list
EOF
    );

    // The selected verb defines the options, so this one accepts them all and
    // passes them through rather than declaring a second copy that could drift.
    $this->ignoreValidationErrors();

    // Hidden from the command list: it is reached by typing nothing, never by
    // name.
    $this->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $application = $this->getApplication();

    if (!$application instanceof Application) {
      // @codeCoverageIgnoreStart
      return Command::FAILURE;
      // @codeCoverageIgnoreEnd
    }

    return $application->find($this->target($this->directory($input)))->run($input, $output);
  }

  /**
   * The command name a directory resolves to.
   *
   * @param string $directory
   *   The target directory.
   *
   * @return string
   *   The command name to run.
   */
  public function target(string $directory): string {
    return Project::isVortex($directory) ? 'configure' : 'install';
  }

  /**
   * The directory to resolve on.
   *
   * Read from the raw input rather than a bound option: this command declares
   * no options of its own, and a destination that does not exist yet is the
   * most ordinary fresh-install case there is.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return string
   *   The target directory.
   */
  protected function directory(InputInterface $input): string {
    $destination = $input->getParameterOption(['--destination', '-d'], NULL, TRUE);

    return is_string($destination) && $destination !== '' ? $destination : (string) getcwd();
  }

}
