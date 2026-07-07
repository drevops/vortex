<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The default command: routes a bare `vortex` invocation by directory state.
 *
 * With no explicit sub-command, an empty directory is treated as a fresh
 * project (install) and an existing one is reconfigured (configure). A saved
 * per-project manifest will later refine this into a proper install / configure
 * / update decision; for now the directory's emptiness is the signal.
 *
 * @package DrevOps\VortexCli\Command
 */
class RouterCommand extends Command {

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

    return $application->find($this->target((string) getcwd()))->run($input, $output);
  }

  /**
   * The command name an empty vs existing directory routes to.
   *
   * @param string $directory
   *   The target directory.
   *
   * @return string
   *   The command name to run.
   */
  public function target(string $directory): string {
    return $this->isEmpty($directory) ? 'install' : 'configure';
  }

  /**
   * Whether a directory has no non-hidden entries.
   *
   * @param string $directory
   *   The directory to inspect.
   *
   * @return bool
   *   TRUE when the directory is missing or holds only hidden entries.
   */
  protected function isEmpty(string $directory): bool {
    $entries = is_dir($directory) ? (scandir($directory) ?: []) : [];

    foreach ($entries as $entry) {
      if ($entry !== '.' && $entry !== '..' && !str_starts_with($entry, '.')) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
