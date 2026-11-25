<?php

/**
 * @file
 * Rename Admin Paths settings.
 */

declare(strict_types=1);

// The replacement for '/user' is read from the environment so that it is
// never committed to the repository.
$user_path = trim((string) getenv('DRUPAL_USER_PATH'), '/');

if ($user_path !== '') {
  $config['rename_admin_paths.settings']['user_path'] = TRUE;
  $config['rename_admin_paths.settings']['user_path_value'] = $user_path;
}
