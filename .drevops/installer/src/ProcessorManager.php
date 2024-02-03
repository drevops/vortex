<?php

namespace DrevOps\Installer;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Processor\AbstractProcessor;
use DrevOps\Installer\Utils\ClassLoader;

/**
 * Processor manager.
 *
 * Orchestrates the processing of all processors.
 */
class ProcessorManager {

  /**
   * Config object.
   */
  protected Config $config;

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function __construct(Config $config,
    protected $output) {
    $this->config = clone $config;
    $this->config->setReadOnly();
  }

  /**
   * Run processing of all processors.
   */
  public function process(): void {
    $classes = ClassLoader::load('Processor', AbstractProcessor::class);

    $classes = array_filter($classes, static function ($class): bool {
      return $class::$weight > 0;
    });

    usort($classes, static function ($a, $b): int {
      return $a::$weight <=> $b::$weight;
    });

    foreach ($classes as $class) {
      (new $class($this->output))->process($this->config, $this->config->getDstDir(), $this->output);
    }
  }

}
