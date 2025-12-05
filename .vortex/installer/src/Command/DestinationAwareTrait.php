<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides destination directory option for commands.
 */
trait DestinationAwareTrait {

  /**
   * Add the destination option to the command.
   */
  protected function addDestinationOption(): void {
    $this->addOption(
      'destination',
      'd',
      InputOption::VALUE_REQUIRED,
      'Target directory for the operation. Defaults to current directory.'
    );
  }

  /**
   * Get the destination directory from input.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input interface.
   *
   * @return string
   *   The validated destination directory path.
   *
   * @throws \InvalidArgumentException
   *   If the destination directory does not exist.
   */
  protected function getDestination(InputInterface $input): string {
    $destination = $input->getOption('destination');

    if ($destination === NULL || $destination === '') {
      return getcwd() ?: '.';
    }

    if (!is_string($destination)) {
      throw new \InvalidArgumentException('Destination must be a string.');
    }

    if (!is_dir($destination)) {
      throw new \InvalidArgumentException(
        sprintf('Destination directory does not exist: %s', $destination)
      );
    }

    return realpath($destination) ?: $destination;
  }

}
