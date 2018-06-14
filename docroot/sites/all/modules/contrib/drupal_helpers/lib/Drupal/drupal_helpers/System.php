<?php

namespace Drupal\drupal_helpers;

/**
 * Class System.
 *
 * @package Drupal\drupal_helpers
 */
class System {

  /**
   * Retrieves the weight of a module, theme or profile from the system table.
   *
   * @param string $name
   *   Machine name of module, theme or profile.
   * @param string $type
   *   Item type as it appears in 'type' column in system table. Can be one of
   *   'module', 'theme' or 'profile'. Defaults to 'module'.
   *
   * @return int
   *   Weight of the specified item.
   */
  public static function weightGet($name, $type = 'module') {
    return db_query("SELECT weight FROM {system} WHERE name = :name AND type = :type", [
      ':name' => $name,
      ':type' => $type,
    ])->fetchField();
  }

  /**
   * Updates the weight of a module, theme or profile in the system table.
   *
   * @param string $name
   *   Machine name of module, theme or profile.
   * @param int $weight
   *   Weight value to set.
   */
  public static function weightSet($name, $weight) {
    db_update('system')->fields(['weight' => $weight])
      ->condition('name', $name)->execute();
  }

  /**
   * Checks the status of a module, theme or profile in the system table.
   *
   * @param string $name
   *   Machine name of module, theme or profile.
   * @param string $type
   *   Item type as it appears in 'type' column in system table. Can be one of
   *   'module', 'theme' or 'profile'. Defaults to 'module'.
   *
   * @return bool
   *   TRUE if the item is enabled, FALSE otherwise.
   */
  public static function isEnabled($name, $type = 'module') {
    $q = db_select('system');
    $q->fields('system', ['name', 'status'])
      ->condition('name', $name, '=')
      ->condition('type', $type, '=');
    $rs = $q->execute();

    return (bool) $rs->fetch()->status;
  }

  /**
   * Checks the status of a module, theme or profile in the system table.
   *
   * @param string $name
   *   Machine name of module, theme or profile.
   * @param string $type
   *   Item type as it appears in 'type' column in system table. Can be one of
   *   'module', 'theme' or 'profile'. Defaults to 'module'.
   *
   * @return bool
   *   TRUE if the item is disabled, FALSE otherwise.
   */
  public static function isDisabled($name, $type = 'module') {
    return !self::isEnabled($name, $type);
  }

  /**
   * Checks whether a module, theme or profile is uninstalled.
   *
   * @param string $name
   *   Machine name of module, theme or profile.
   * @param string $type
   *   Item type as it appears in 'type' column in system table. Can be one of
   *   'module', 'theme' or 'profile'. Defaults to 'module'.
   *
   * @return bool
   *   TRUE if the item is uninstalled, FALSE otherwise.
   */
  public static function isUninstalled($name, $type = 'module') {
    $q = db_select('system');
    $q->fields('system', ['name', 'schema_version'])
      ->condition('name', $name, '=')
      ->condition('type', $type, '=');
    $rs = $q->execute();

    return (int) $rs->fetch()->schema_version === -1;
  }

}
