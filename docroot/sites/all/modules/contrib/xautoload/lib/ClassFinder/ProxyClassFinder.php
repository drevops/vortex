<?php

namespace Drupal\xautoload\ClassFinder;

use Drupal\xautoload\Adapter\DrupalExtensionAdapter;
use Drupal\xautoload\ClassLoader\AbstractClassLoader;
use Drupal\xautoload\FinderOperation\FinderOperationInterface;

/**
 * A placeholder class finder. Used to postpone expensive operations until they
 * are actually needed.
 */
class ProxyClassFinder
  extends AbstractClassLoader
  implements ClassFinderInterface {

  /**
   * @var ExtendedClassFinderInterface
   *   The actual class finder.
   */
  protected $finder;

  /**
   * @var DrupalExtensionAdapter
   */
  protected $helper;

  /**
   * @var FinderOperationInterface[]
   *   Operations to run when the actual finder is initialized.
   */
  protected $scheduledOperations = array();

  /**
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * @param ExtendedClassFinderInterface $finder
   * @param DrupalExtensionAdapter $helper
   */
  function __construct($finder, $helper) {
    $this->finder = $finder;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  function loadClass($class) {
    $this->initFinder();
    $this->finder->loadClass($class);
  }

  /**
   * {@inheritdoc}
   */
  function apiFindFile($api, $class) {
    $this->initFinder();

    return $this->finder->apiFindFile($api, $class);
  }

  /**
   * @param FinderOperationInterface $operation
   */
  function onFinderInit($operation) {
    if (!$this->initialized) {
      $this->scheduledOperations[] = $operation;
    }
    else {
      $operation->operateOnFinder($this->finder, $this->helper);
    }
  }

  /**
   * @return ClassFinderInterface
   */
  function getFinder() {
    $this->initFinder();

    return $this->finder;
  }

  /**
   * Initialize the finder and run scheduled operations.
   */
  protected function initFinder() {
    if (!$this->initialized) {
      foreach ($this->scheduledOperations as $operation) {
        $operation->operateOnFinder($this->finder, $this->helper);
      }
      $this->initialized = TRUE;
    }
  }
}
