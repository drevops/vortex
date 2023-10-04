<?php

namespace DrevOps\Installer;

use DrevOps\Installer\Utils\ClassLoader;

class ProcessorManager {

  /**
   * Config object.
   *
   * @var \DrevOps\Installer\Bag\Config
   */
  protected $config;

  /**
   * Output object.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  public function __construct($config, $output) {
    $this->config = clone $config;
    $this->config->setReadOnly();
    $this->output = $output;
  }

  /**
   * Run processing of all processors.
   */
  public function process() {
    $classes = ClassLoader::load('Processor', 'DrevOps\\Installer\\Processor\\AbstractProcessor');

    $classes = array_filter($classes, function ($class) {
      return $class::$weight > 0;
    });

    usort($classes, function ($a, $b) {
      return $a::$weight <=> $b::$weight;
    });

    foreach ($classes as $class) {
      (new $class($this->output))->process($this->config, $this->config->getDstDir(), $this->output);
    }
  }

}
