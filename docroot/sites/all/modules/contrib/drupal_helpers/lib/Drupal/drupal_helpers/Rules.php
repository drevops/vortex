<?php

namespace Drupal\drupal_helpers;

use DrupalUpdateException;
use Exception;

/**
 * Class Rules.
 *
 * @package Drupal\drupal_helpers
 */
class Rules {

  /**
   * Load a rules configuration.
   *
   * @param string $rule_name
   *   Machine name of the Rule.
   *
   * @return bool|object
   *   Rules configuration object or FALSE if configuration was not found.
   */
  protected static function load($rule_name) {
    $t = get_t();

    $rules_config = rules_config_load($rule_name);
    if (!$rules_config) {
      General::messageSet($t('Skipped: rules !rule_name was not found', ['!rule_name' => $rule_name]));

      return FALSE;
    }

    return $rules_config;
  }

  /**
   * Set Active.
   *
   * Set the active property for a rules configuration.
   *
   * @param string $rule_name
   *   Machine name of the Rule.
   * @param bool $value
   *   Set rule active property to value.
   *
   * @throws DrupalUpdateException
   */
  public static function setActive($rule_name, $value) {
    $t = get_t();

    $action = $value ? 'enable' : 'disable';
    $actioned = $action . 'd';
    $replacements = [
      '!rule_name' => $rule_name,
      '!action' => $action,
      '!actioned' => $actioned,
    ];

    try {
      $rules_config = self::load($rule_name);
      if ($rules_config) {
        $rules_config->active = (bool) $value;
        $rules_config->save();
        General::messageSet($t('The rules !rule_name has been !actioned.', $replacements));
      }
    }
    catch (Exception $e) {
      $replacements['@error_message'] = $e->getMessage();
      throw new DrupalUpdateException($t('Failed to !action rules !rule_name: @error_message', $replacements), $e->getCode(), $e);
    }
  }

  /**
   * Disable a rules configuration.
   *
   * @param string $rule_name
   *   Machine name of the Rule.
   *
   * @throws \DrupalUpdateException
   */
  public static function disable($rule_name) {
    self::setActive($rule_name, FALSE);
  }

  /**
   * Enable a rules configuration.
   *
   * @param string $rule_name
   *   Machine name of the Rule.
   *
   * @throws \DrupalUpdateException
   */
  public static function enable($rule_name) {
    self::setActive($rule_name, TRUE);
  }

  /**
   * Delete a rules configuration.
   *
   * @param string $rule_name
   *   Machine name of the Rule.
   *
   * @throws \DrupalUpdateException
   */
  public static function delete($rule_name) {
    $t = get_t();

    $replacements = [
      '!rule_name' => $rule_name,
    ];

    try {
      $rules_config = self::load($rule_name);
      if ($rules_config) {
        $rules_config->delete();

        General::messageSet($t('The rules !rule_name has been deleted.', $replacements));
      }
    }
    catch (Exception $e) {
      $replacements['@error_message'] = $e->getMessage();
      throw new DrupalUpdateException($t('Failed to delete rules !rule_name: @error_message', $replacements), $e->getCode(), $e);
    }
  }

}
