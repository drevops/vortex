<?php

namespace Drupal\drupal_helpers;

use Exception;

/**
 * Class Field.
 *
 * @package Drupal\drupal_helpers
 */
class Field {

  /**
   * Delete a field.
   *
   * Remove all instances of a field from all the entity bundles it has been
   * attached to and then delete and purge field's data from the database.
   *
   * @param string $field_name
   *   Machine name of the Field.
   *
   * @throws \Exception
   */
  public static function delete($field_name) {
    $t = get_t();

    try {
      $field = field_info_field($field_name);
      $replacements = ['!field' => $field_name];
      if (!$field) {
        General::messageSet($t('Skipped: !field was not found', $replacements));

        return;
      }

      if (isset($field['bundles']) && is_array($field['bundles'])) {
        foreach ($field['bundles'] as $entity_type => $bundles) {
          $replacements['!entity'] = $entity_type;
          if (is_array($bundles)) {
            foreach ($bundles as $entity_bundle) {
              self::deleteInstance($field_name, $entity_type, $entity_bundle);
            }
          }
        }
      }

      field_delete_field($field_name);

      $batch_size = variable_get('field_purge_batch_size', 10);
      field_purge_batch($batch_size);

      General::messageSet($t('The field !field has been deleted.', $replacements));
    }
    catch (Exception $e) {
      $replacements['@error_message'] = $e->getMessage();
      $message = 'Failed to delete !field: @error_message';

      throw new Exception($t($message, $replacements), $e->getCode(), $e);
    }
  }

  /**
   * Delete an Instance of a Field.
   *
   * Delete a specific field instance attached to one entity type without
   * deleting the field itself.
   *
   * @param string $field_name
   *   Machine name of the Field.
   * @param string $entity_type
   *   Machine name of the Entity type.
   * @param string $entity_bundle
   *   Machine name of the Entity Bundle.
   *
   * @throws \Exception
   */
  public static function deleteInstance($field_name, $entity_type, $entity_bundle) {
    $t = get_t();
    $replacements = [
      '!field' => $field_name,
      '!entity' => $entity_type,
      '!bundle' => $entity_bundle,
    ];

    try {
      $instance = field_info_instance($entity_type, $field_name, $entity_bundle);
      if ($instance) {
        field_delete_instance($instance, FALSE);
        $message = 'Success: deleted the field !field from the !entity !bundle content type.';
      }
      else {
        $message = 'Skipped: the !field was not found for the !entity !bundle content type.';
      }
    }
    catch (Exception $e) {
      $replacements['@error_message'] = $e->getMessage();
      $message = 'Problem removing the field !field from the !entity !bundle content type - @error_message';

      throw new Exception($t($message, $replacements), $e->getCode(), $e);
    }

    General::messageSet($t($message, $replacements));
  }

  /**
   * Get Field Configuration Data.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return array|bool
   *   Field configuration array else FALSE.
   *
   * @throws \Exception
   */
  public static function getFieldConfigData($field_name) {
    try {
      $query = '
        SELECT CAST(data AS CHAR(10000) CHARACTER SET utf8)
        FROM {field_config}
        WHERE field_name = :field_name
      ';
      $result = db_query($query, [':field_name' => $field_name]);
      $config = $result->fetchField();
    }
    catch (Exception $e) {
      // Pass on the exception with an explanation.
      $message = sprintf(
        'Failed to get field config data for %s : %s',
        $field_name, $e->getMessage()
      );
      throw new Exception($message, $e->getCode(), $e);
    }

    if ($config) {
      return unserialize($config);
    }

    return FALSE;
  }

  /**
   * Set Field Configuration Data.
   *
   * @param string $field_name
   *   Field name.
   * @param array $config
   *   Field configuration array.
   *
   * @return bool
   *   Success TRUE else FALSE.
   *
   * @throws \Exception
   */
  public static function setFieldConfigData($field_name, array $config) {
    try {
      $data = serialize($config);
      $result = db_update('field_config')
        ->fields(['data' => $data])
        ->condition('field_name', $field_name)
        ->execute();
    }
    catch (Exception $e) {
      // Pass on the exception with an explanation.
      $message = sprintf(
        'Failed to set field config data for %s : %s',
        $field_name, $e->getMessage()
      );
      throw new Exception($message, $e->getCode(), $e);
    }

    return ($result->rowCount() > 0);
  }

  /**
   * Change Text Field Max Length.
   *
   * Change the max length of a Text field, even if it contains content. Any
   * text content longer than the new max length will be trimmed permanently.
   *
   * All changes are rolled back if there is a failure.
   *
   * @param string $field_name
   *   Field name.
   * @param int $max_length
   *   Field length in characters.
   *
   * @throws \Exception
   */
  public static function changeTextFieldMaxLength($field_name, $max_length) {
    $db_txn = db_transaction();

    try {
      // Modify field data and revisions.
      foreach (['field_data', 'field_revision'] as $prefix) {
        self::modifyTextFieldValueLength("{$prefix}_{$field_name}", $max_length);
      }
      // Update field config.
      self::updateTextFieldConfigMaxLength($field_name, $max_length);
    }
    catch (Exception $e) {
      // Something went wrong, so roll back all changes.
      $db_txn->rollback();

      // Pass on the exception with an explanation.
      $message = sprintf(
        'Failed to change field %s max length to %d : %s',
        $field_name, $max_length, $e->getMessage()
      );
      throw new Exception($message, $e->getCode(), $e);
    }

    General::messageSet(sprintf('Text field %s max length changed to %d', $field_name, $max_length));
  }

  /**
   * Modify a Text Field Table Value Column Length.
   *
   * @param string $field_table
   *   Drupal field table name (ie. field_data_field_textfield).
   * @param int $value_length
   *   Length in characters.
   */
  private static function modifyTextFieldValueLength($field_table, $value_length) {
    $field_value_column = $field_table . '_value';
    $query_alter = sprintf(
      'ALTER TABLE {%s} MODIFY %s VARCHAR(%d)',
      $field_table, $field_value_column, $value_length
    );
    db_query($query_alter);
  }

  /**
   * Update Text Field Configuration for Max Length.
   *
   * @param string $field_name
   *   Text field name.
   * @param int $max_length
   *   Field length in characters.
   *
   * @throws \Exception
   */
  private static function updateTextFieldConfigMaxLength($field_name, $max_length) {
    $config = self::getFieldConfigData($field_name);
    if (is_array($config)) {
      $config['settings']['max_length'] = $max_length;
      self::setFieldConfigData($field_name, $config);
    }
    else {
      throw new Exception(sprintf('No config data found for field %s', $field_name));
    }
  }

}
