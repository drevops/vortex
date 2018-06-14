<?php

namespace Drupal\xautoload\DrupalSystem;

class MockDrupalSystem implements DrupalSystemInterface {

  /**
   * @var array
   */
  protected $variables;

  /**
   * @var string[]
   */
  protected $activeExtensions;

  /**
   * @param array $variables
   * @param string[] $active_extensions
   */
  function __construct(array $variables, array $active_extensions) {
    $this->variables = $variables;
    $this->activeExtensions = $active_extensions;
  }

  /**
   * {@inheritdoc}
   */
  function variableGet($name, $default = NULL) {
    return $this->variables[$name] ? : $default;
  }

  /**
   * {@inheritdoc}
   */
  function drupalGetFilename($type, $name) {
    // Simply assume that everything is a module.
    return "test://modules/$name/$name.module";
  }

  /**
   * {@inheritdoc}
   */
  function getExtensionTypes($extension_names) {
    // Simply assume that everything is a module.
    return array_fill_keys($extension_names, 'module');
  }

  /**
   * {@inheritdoc}
   */
  function getActiveExtensions() {
    return $this->activeExtensions;
  }
}
