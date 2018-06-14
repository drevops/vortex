<?php

namespace Drupal\xautoload\FinderOperation;

use Drupal\xautoload\ClassLoader\AbstractClassLoaderDecorator;

class LoaderSetFinderOperation implements FinderOperationInterface {

  /**
   * @var AbstractClassLoaderDecorator
   */
  protected $loader;

  /**
   * @param AbstractClassLoaderDecorator $loader
   */
  function __construct($loader) {
    $this->loader = $loader;
  }

  /**
   * {@inheritdoc}
   */
  function operateOnFinder($finder, $helper) {
    $this->loader->setFinder($finder);
  }
}