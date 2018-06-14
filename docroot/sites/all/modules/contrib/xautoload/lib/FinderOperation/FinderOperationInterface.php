<?php

namespace Drupal\xautoload\FinderOperation;

use Drupal\xautoload\Adapter\DrupalExtensionAdapter;
use Drupal\xautoload\ClassFinder\ExtendedClassFinderInterface;

interface FinderOperationInterface {

  /**
   * @param ExtendedClassFinderInterface $finder
   * @param DrupalExtensionAdapter $helper
   */
  function operateOnFinder($finder, $helper);
}