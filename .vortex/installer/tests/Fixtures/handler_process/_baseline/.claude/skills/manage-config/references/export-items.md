# Export single configuration items

The script writes the named items into the sync directory in the exact format of `drush config:export`. It reads each item from Drupal's export storage, the storage a full export copies from, so the same export rules apply: modules listed in `$settings['config_exclude_modules']` are left out, and items owned by an active configuration split are not in it.

Save it as `.artifacts/tmp/<skill>/export-items.php`, where `<skill>` is the name of the skill you're following:

```php
<?php

/**
 * @file
 * Writes named configuration items to the sync directory in export format.
 *
 * Usage: ahoy drush php:script /app/.artifacts/tmp/<skill>/export-items.php <name> [<name> ...]
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

// Drush passes the arguments after the script path in $extra.
$names = $extra ?? [];

if (empty($names)) {
  print "Pass at least 1 configuration name.\n";
  return;
}

$export = \Drupal::service('config.storage.export');
$sync = \Drupal::service('config.storage.sync');
$directory = Settings::get('config_sync_directory');

foreach ($names as $name) {
  if (!$export->exists($name)) {
    printf("SKIPPED %s: not in the export storage, so the active database lacks it, an excluded module owns it or a split owns it.\n", $name);
    continue;
  }

  $sync->write($name, $export->read($name));
  printf("WROTE   %s/%s.yml\n", $directory, $name);
}
```

Run it with 1 or more names:

```shell
ahoy drush php:script /app/.artifacts/tmp/manage-config/export-items.php system.site views.view.frontpage
```

Each `WROTE` line names a file that now matches the database. Review it with `git diff -- config/default/<name>.yml`. A new file shows up in `git status --short config/` instead.

A `SKIPPED` line means a full export wouldn't write that item to `config/default` either. Find out why before you work around it:

- The database doesn't hold the item: `ahoy drush config:get <name>` fails with `Config <name> does not exist`.
- An excluded module owns it: the module appears in a `config_exclude_modules` line in `web/sites/default/includes/modules/settings.<module>.php`.
- An active configuration split owns it: the item is listed in that split's definition, and a full export writes it to the split's directory.

The script writes only to the sync directory. It never removes a file, so an item deleted from the database still needs its file removed by hand, after the developer confirmed.
