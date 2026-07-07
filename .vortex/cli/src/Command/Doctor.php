<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Diagnoses the local environment for common problems.
 *
 * A read-only health check: it never changes the project. Grow the list in
 * requirements() as more checks are needed.
 *
 * @package DrevOps\VortexCli\Command
 */
class Doctor extends Command {

  /**
   * The executable finder (overridable for tests).
   */
  protected ?ExecutableFinder $executableFinder = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('doctor')
      ->setDescription('Diagnose the local environment for common problems.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $ok = TRUE;

    foreach ($this->requirements() as $tool) {
      $found = $this->getExecutableFinder()->find($tool) !== NULL;
      $ok = $ok && $found;
      $output->writeln(sprintf('[%s] %s', $found ? 'OK' : 'MISSING', $tool));
    }

    return $ok ? Command::SUCCESS : Command::FAILURE;
  }

  /**
   * The required executables to check for.
   *
   * @return string[]
   *   The executable names.
   */
  protected function requirements(): array {
    return ['git', 'docker'];
  }

  /**
   * Get the executable finder.
   *
   * @return \Symfony\Component\Process\ExecutableFinder
   *   The executable finder.
   */
  protected function getExecutableFinder(): ExecutableFinder {
    return $this->executableFinder ??= new ExecutableFinder();
  }

  /**
   * Set the executable finder.
   *
   * @param \Symfony\Component\Process\ExecutableFinder $finder
   *   The executable finder.
   */
  public function setExecutableFinder(ExecutableFinder $finder): void {
    $this->executableFinder = $finder;
  }

}
