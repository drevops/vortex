<?php

namespace Drupal\drupal_helpers;

/**
 * Class Module.
 *
 * @package Drupal\drupal_helpers
 */
class Module extends System {

  /**
   * Enables a module and performs some error checking.
   *
   * @param string $module
   *   Module name to enable.
   * @param bool $enable_dependencies
   *   Flag to enable module's dependencies. Defaults to TRUE.
   *
   * @return bool
   *   Returns TRUE if module was enabled successfully, \DrupalUpdateException
   *   is thrown otherwise.
   *
   * @throws \DrupalUpdateException
   *   Throws exception if module was not enabled.
   */
  public static function enable($module, $enable_dependencies = TRUE) {
    if (self::isEnabled($module)) {
      General::messageSet(format_string('Module "@module" already exists - Aborting!', [
        '@module' => $module,
      ]));

      return TRUE;
    }
    $ret = module_enable([$module], $enable_dependencies);
    if ($ret) {
      // Double check that the installed.
      if (self::isEnabled($module)) {
        General::messageSet(format_string('Module "@module" was successfully enabled.', [
          '@module' => $module,
        ]));

        return TRUE;
      }
    }

    throw new \DrupalUpdateException(format_string('Module "@module" could not enabled.', [
      '@module' => $module,
    ]));
  }

  /**
   * Disables a module and performs some error checking.
   *
   * @param string $module
   *   Module name to disable.
   * @param bool $disable_dependents
   *   If TRUE, dependent modules will automatically be added and disabled in
   *   the correct order.
   *
   * @return bool
   *   Returns TRUE if module was disabled successfully, \DrupalUpdateException
   *   is thrown otherwise.
   *
   * @throws \DrupalUpdateException
   *   Throws exception if module was not disabled.
   */
  public static function disable($module, $disable_dependents = TRUE) {
    if (self::isDisabled($module)) {
      General::messageSet(format_string('Module "@module" is already disabled - Aborting!', [
        '@module' => $module,
      ]));

      return TRUE;
    }

    module_disable([$module], $disable_dependents);

    if (self::isDisabled($module)) {
      General::messageSet(format_string('Module "@module" was successfully disabled.', [
        '@module' => $module,
      ]));

      return TRUE;
    }

    throw new \DrupalUpdateException(format_string('Module "@module" could not disabled.', [
      '@module' => $module,
    ]));
  }

  /**
   * Uninstalls a module.
   *
   * @param string $module
   *   Module name to uninstall.
   * @param bool $uninstall_dependents
   *   If TRUE, dependent modules will automatically be disabled and uninstalled
   *   in the correct order.
   *
   * @return bool
   *   Returns TRUE if module was uninstalled successfully,
   *   \DrupalUpdateException is thrown otherwise.
   *
   * @throws \DrupalUpdateException
   *   Throws exception if module was not uninstalled.
   */
  public static function uninstall($module, $uninstall_dependents = TRUE) {
    self::disable($module, $uninstall_dependents);
    drupal_uninstall_modules([$module], $uninstall_dependents);

    if (self::isUninstalled($module)) {
      General::messageSet(format_string('Module "@module" was successfully uninstalled.', [
        '@module' => $module,
      ]));

      return TRUE;
    }

    throw new \DrupalUpdateException(format_string('Module "@module" could not uninstalled.', [
      '@module' => $module,
    ]));
  }

  /**
   * Removes already uninstalled module.
   *
   * @param string $module
   *   Module name to remove.
   */
  public static function remove($module) {
    db_update('system')
      ->fields(['status' => '0'])
      ->condition('name', $module)
      ->execute();

    db_delete('cache_bootstrap')
      ->condition('cid', 'system_list')
      ->execute();

    db_delete('system')
      ->condition('name', $module)
      ->execute();

    General::messageSet(format_string('Removed traces of module "@module".', [
      '@module' => $module,
    ]));
  }

}
