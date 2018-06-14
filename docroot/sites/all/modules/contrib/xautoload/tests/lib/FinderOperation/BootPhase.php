<?php

namespace Drupal\xautoload\Tests\FinderOperation;

use Drupal\xautoload\FinderOperation\FinderOperationInterface;

class BootPhase implements FinderOperationInterface {

  /**
   * @var string[]
   */
  protected $extensions;

  /**
   * @param string[] $extensions
   */
  function __construct(array $extensions) {
    $this->extensions = $extensions;
  }

  /**
   * {@inheritdoc}
   */
  function operateOnFinder($finder, $helper) {
    $helper->registerExtensions($this->extensions);
  }
}