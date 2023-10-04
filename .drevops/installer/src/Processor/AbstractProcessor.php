<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract processor.
 *
 * Processors are used to modify the codebase after the installation.
 * They usually work with files in the destination.
 *
 * @package DrevOps\Installer\Processor
 */
abstract class AbstractProcessor {

  /**
   * Weight of the processor.
   *
   * The higher the weight, the later the processor will be run.
   *
   * @var int
   *   The weight greater than 0. 0 means that the processor will not be run
   *   automatically.
   */
  protected static $weight = 0;

  /**
   * Run processing.
   *
   * @param \DrevOps\Installer\Bag\Config $config
   *   The configuration.
   * @param string $dir
   *   The directory to process.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output to write messages to.
   */
  abstract public function run(Config $config, string $dir, OutputInterface $output);

}
