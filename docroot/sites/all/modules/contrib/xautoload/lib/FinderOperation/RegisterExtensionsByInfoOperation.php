<?php

namespace Drupal\xautoload\FinderOperation;

class RegisterExtensionsByInfoOperation implements FinderOperationInterface {

  /**
   * @var string[]
   *   Array of module info objects, with numeric keys.
   */
  protected $extensions;

  /**
   * @param string[] $extensions
   *   Array of module info objects, with numeric keys.
   */
  function __construct($extensions) {
    $this->extensions = $extensions;
  }

  /**
   * {@inheritdoc}
   */
  function operateOnFinder($finder, $helper) {

    // Register the namespaces / prefixes for those modules.
    $helper->registerExtensions($this->extensions);
  }
}