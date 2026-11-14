# Verify script

The script prints the environment type that Drupal runs with, then 1 line per configuration name. It compares the database with the code as a deployment would import it: it reads `config/default` through Drupal's import transformation, so excluded modules and any active configuration split apply.

Save it as `.artifacts/tmp/reconcile-config/verify.php`:

```php
<?php

/**
 * @file
 * Reports how a configuration import would treat each named item.
 *
 * Usage: ahoy drush php:script /app/.artifacts/tmp/reconcile-config/verify.php [<name> ...]
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

printf("Environment type: %s\n", Settings::get('environment'));

// Drush passes the arguments after the script path in $extra.
$names = $extra ?? [];

$active = \Drupal::service('config.storage');
$code = \Drupal::service('config.import_transformer')->transform(\Drupal::service('config.storage.sync'));

foreach ($names as $name) {
  $in_db = $active->exists($name);
  $in_code = $code->exists($name);

  if ($in_db && $in_code) {
    $db_data = $active->read($name);
    $code_data = $code->read($name);

    if ($db_data === $code_data) {
      $result = 'MATCH';
    }
    elseif (isset($db_data['uuid']) && $db_data['uuid'] !== ($code_data['uuid'] ?? NULL)) {
      $result = 'CODE WINS (uuid differs: the import deletes and recreates the entity)';
    }
    else {
      $result = 'CODE WINS';
    }
  }
  elseif ($in_code) {
    $result = 'CODE WINS (the import creates it)';
  }
  elseif ($in_db) {
    $result = 'DELETED ON DEPLOY';
  }
  else {
    $result = 'ABSENT';
  }

  printf("%s: %s\n", $name, $result);
}
```

Run it with the environment type of the deployed environment, and pass every item you decided on:

```shell
ENVIRONMENT_TYPE=prod ahoy drush php:script /app/.artifacts/tmp/reconcile-config/verify.php views.view.archive system.site
```

The results mean the following:

| Result              | Database | Code after the transformation | What the next import does                  |
|---------------------|----------|-------------------------------|--------------------------------------------|
| `MATCH`             | Yes      | Yes, identical                | Nothing                                    |
| `CODE WINS`         | Yes      | Yes, different                | Overwrites the database value              |
| `CODE WINS (uuid differs ...)` | Yes | Yes, with another `uuid` | Deletes the entity and creates it again |
| `CODE WINS (the import creates it)` | No | Yes                 | Creates the item                           |
| `DELETED ON DEPLOY` | Yes      | No                            | Deletes the item                           |
| `ABSENT`            | No       | No                            | Nothing                                    |

A `CODE WINS (uuid differs ...)` result after an Export decision means the export didn't take. Run the export again and check the `uuid` line in the file.

The script reads the stored configuration, so settings overrides in `web/sites/default/includes/modules/settings.<module>.php` don't affect its results. It changes no configuration and no files.
