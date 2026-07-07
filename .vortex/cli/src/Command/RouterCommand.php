<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Utils\File;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The default command: routes a bare `vortex` invocation by project state.
 *
 * With no explicit sub-command, a directory that already holds a Vortex project
 * - detected from its README badge, the same signal the installer uses to spot
 * an existing project - is reconfigured; anything else is a fresh install.
 *
 * @package DrevOps\VortexCli\Command
 */
class RouterCommand extends Command {

  /**
   * The directory to route on (defaults to the working directory).
   */
  protected ?string $directory = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('route')
      ->setHidden(TRUE)
      ->setDescription('Route a bare invocation to install or configure.');

    // Accept whatever options the delegated command defines.
    $this->ignoreValidationErrors();
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

    return $application->find($this->target($this->directory()))->run($input, $output);
  }

  /**
   * The command name a directory routes to.
   *
   * @param string $directory
   *   The target directory.
   *
   * @return string
   *   The command name to run.
   */
  public function target(string $directory): string {
    return $this->isVortexProject($directory) ? 'configure' : 'install';
  }

  /**
   * Set the directory to route on.
   *
   * @param string $directory
   *   The directory.
   */
  public function setDirectory(string $directory): void {
    $this->directory = $directory;
  }

  /**
   * The directory to route on.
   *
   * @return string
   *   The set directory, or the current working directory.
   */
  protected function directory(): string {
    return $this->directory ?? $this->currentDirectory();
  }

  /**
   * The current working directory.
   *
   * @return string
   *   The working directory.
   */
  protected function currentDirectory(): string {
    // @codeCoverageIgnoreStart
    return (string) getcwd();
    // @codeCoverageIgnoreEnd
  }

  /**
   * Whether a directory holds an existing Vortex project.
   *
   * @param string $directory
   *   The directory to inspect.
   *
   * @return bool
   *   TRUE when the directory's README carries the Vortex badge.
   */
  protected function isVortexProject(string $directory): bool {
    return File::contains($directory . '/README.md', '/badge\/Vortex-/');
  }

}
