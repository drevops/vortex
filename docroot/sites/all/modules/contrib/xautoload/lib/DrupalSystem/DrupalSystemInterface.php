<?php

namespace Drupal\xautoload\DrupalSystem;

interface DrupalSystemInterface {

  /**
   * Replacement of variable_get().
   *
   * @param string $name
   * @param mixed $default
   *
   * @return mixed
   */
  function variableGet($name, $default = NULL);

  /**
   * Replacement of drupal_get_filename(), but returning an absolute path.
   *
   * @param string $type
   * @param string $name
   *
   * @return string
   *   The result of drupal_get_filename() with DRUPAL_ROOT . '/' prepended.
   */
  function drupalGetFilename($type, $name);

  /**
   * @param string[] $extension_names
   *   Extension names.
   *
   * @return string[]
   *   Extension types by extension name.
   */
  function getExtensionTypes($extension_names);

  /**
   * @return string[]
   *   Extension types by extension name.
   */
  function getActiveExtensions();
}
