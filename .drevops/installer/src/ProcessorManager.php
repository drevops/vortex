<?php

namespace DrevOps\Installer;

use DrevOps\Installer\Processor\AbstractProcessor;
use DrevOps\Installer\Utils\ClassLoader;

/**
 *
 */
class ProcessorManager {

  /**
   * Config object.
   *
   * @var \DrevOps\Installer\Bag\Config
   */
  protected $config;

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function __construct($config, /**
                                        * Output object.
                                        */
  protected $output) {
    $this->config = clone $config;
    $this->config->setReadOnly();
  }

  /**
   * Run processing of all processors.
   */
  public function process(): void {
    $classes = ClassLoader::load('Processor', AbstractProcessor::class);

    $classes = array_filter($classes, static function ($class) : bool {
        return $class::$weight > 0;
    });

    usort($classes, static function ($a, $b) : int {
        return $a::$weight <=> $b::$weight;
    });

    foreach ($classes as $class) {
      (new $class($this->output))->process($this->config, $this->config->getDstDir(), $this->output);
    }
  }

}
