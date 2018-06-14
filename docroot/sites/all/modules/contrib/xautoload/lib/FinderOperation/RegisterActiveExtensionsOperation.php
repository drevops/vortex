<?php

namespace Drupal\xautoload\FinderOperation;

class RegisterActiveExtensionsOperation implements FinderOperationInterface {

  /**
   * {@inheritdoc}
   */
  function operateOnFinder($finder, $helper) {
    $helper->registerActiveExtensions();
  }
}