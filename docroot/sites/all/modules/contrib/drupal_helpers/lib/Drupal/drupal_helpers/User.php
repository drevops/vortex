<?php

namespace Drupal\drupal_helpers;

/**
 * Class User.
 *
 * @package Drupal\drupal_helpers
 */
class User {

  /**
   * Helper to create user with specified fields and roles.
   *
   * @param array $edit_overrides
   *   Array of user override fields. Value of an element with a 'mail' key
   *   is required.
   * @param array $role_names
   *   Optional array of role names to be assigned.
   *
   * @return bool|Object
   *   User account object or FALSE if user was not created.
   */
  public static function create(array $edit_overrides, array $role_names = []) {
    // Mail is an absolute minimum that we require.
    if (!isset($edit_overrides['mail'])) {
      return FALSE;
    }

    $edit['mail'] = Random::email();
    $edit['name'] = $edit['mail'];
    $edit['pass'] = user_password();
    $edit['status'] = 1;
    $edit['roles'] = [];
    if (!empty($role_names)) {
      $role_names = is_array($role_names) ? $role_names : [$role_names];
      foreach ($role_names as $role_name) {
        $role = user_role_load_by_name($role_name);
        $edit['roles'][$role->rid] = $role->rid;
      }
    }

    // Merge fields with provided $edit_overrides.
    $edit = array_merge($edit, $edit_overrides);

    // Build an empty user object, including all default fields.
    $account = drupal_anonymous_user();
    foreach (field_info_instances('user', 'user') as $field_name => $info) {
      if (!isset($account->{$field_name})) {
        $account->{$field_name} = [];
      }
    }

    $account = user_save($account, $edit);

    if (!$account && empty($account->uid)) {
      return FALSE;
    }

    // Add raw password just in case if we need to login with this user.
    $account->pass_raw = $edit['pass'];

    return $account;
  }

}
