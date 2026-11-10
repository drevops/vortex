# Error probes

Copy a probe into `.artifacts/tmp/debug-errors/`, edit the values at its top, and run it with `ahoy drush php:script /app/.artifacts/tmp/debug-errors/<name>.php`. Edit the values in the file for each run instead of passing arguments. Delete the files when the investigation is done.

To step through a probe with Xdebug, turn Xdebug on with `ahoy debug` and add `--xdebug`: `ahoy drush --xdebug php:script /app/.artifacts/tmp/debug-errors/<name>.php`. Without the flag, Drush turns Xdebug off.

## Request probe

Sends 1 request through Drupal's HTTP kernel with its error handling turned off, so the original exception and every previous exception print with their traces. The page that a browser gets shows only the generic error message.

```php
<?php

/**
 * @file
 * Sends 1 internal request and prints any exception with its trace.
 */

declare(strict_types=1);

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

// The path to request, and the user to request it as. 0 is anonymous.
$path = '/node/1';
$uid = 0;

if ($uid > 0) {
  $account = User::load($uid);
  if ($account === NULL) {
    throw new \RuntimeException(sprintf('User %d does not exist.', $uid));
  }
  \Drupal::service('account_switcher')->switchTo($account);
}

try {
  // With $catch set to FALSE, the kernel rethrows the exception instead of
  // rendering an error page.
  $response = \Drupal::service('http_kernel')->handle(Request::create($path), HttpKernelInterface::MAIN_REQUEST, FALSE);
  printf("Status: %d\n", $response->getStatusCode());
}
catch (\Throwable $e) {
  for ($current = $e; $current !== NULL; $current = $current->getPrevious()) {
    printf("%s: %s\nat %s:%d\n%s\n\n", get_class($current), $current->getMessage(), $current->getFile(), $current->getLine(), $current->getTraceAsString());
  }
}
```

Read the output:

- The first frame of the trace that points into `web/modules/custom/` or the custom theme is usually where the fix goes.
- A `NotFoundHttpException` or `AccessDeniedHttpException` is a 404 or a 403, not a crash. Check the path and the user.
- Drupal bypasses Page Cache and Dynamic Page Cache on the command line, so the request runs the full render path every time.
- When the probe fails with an error about the session or the request stack, the route depends on a real browser request. Reproduce it with `curl` and read the watchdog log instead.

## Call probe

Runs 1 piece of code on its own and prints the exception it throws. Replace the body of the `try` block with the call that fails, such as a service method, a form builder or an entity render.

```php
<?php

/**
 * @file
 * Runs 1 call and prints any exception with its trace.
 */

declare(strict_types=1);

// The node to render, and the view mode.
$nid = 1;
$view_mode = 'full';

try {
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
  if ($node === NULL) {
    throw new \RuntimeException(sprintf('Node %d does not exist.', $nid));
  }

  $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);
  \Drupal::service('renderer')->renderInIsolation($build);
  print "Finished without an exception.\n";
}
catch (\Throwable $e) {
  for ($current = $e; $current !== NULL; $current = $current->getPrevious()) {
    printf("%s: %s\nat %s:%d\n%s\n\n", get_class($current), $current->getMessage(), $current->getFile(), $current->getLine(), $current->getTraceAsString());
  }
}
```

A probe that finishes without an exception for code that fails on the page points at the request: the route, the user, the session or a cached value.

## Settings probe

Prints the settings that decide how errors show and which local overrides loaded.

```php
<?php

/**
 * @file
 * Prints the environment type, error levels, service files and cache bins.
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

printf("Environment type: %s\n", Settings::get('environment'));

// getEditable() reads the stored value without the overrides from settings.
printf("Error level used: %s\n", \Drupal::config('system.logging')->get('error_level'));
printf("Error level stored: %s\n", \Drupal::configFactory()->getEditable('system.logging')->get('error_level'));

print "Service files:\n";
foreach (Settings::get('container_yamls', []) as $file) {
  printf("  %s\n", $file);
}

printf("rebuild.php access: %s\n", Settings::get('rebuild_access', FALSE) ? 'on' : 'off');

$bins = Settings::get('cache', [])['bins'] ?? [];
foreach (['render', 'page', 'dynamic_page_cache'] as $bin) {
  printf("Cache bin %s: %s\n", $bin, $bins[$bin] ?? 'default backend');
}
```

Read the output:

- `Error level used: verbose` and a `services.local.yml` entry under the service files mean `settings.local.php` loaded.
- `Error level used: all` with `Environment type: local` means only `settings.system.php` applied, so pages show messages without a backtrace.
- A cache bin set to `cache.backend.null` doesn't cache. An error that only a cached page shows can't happen locally while it's set.
- The command line and PHP-FPM read the same settings files. `DRUPAL_SETTINGS_LOCAL_SKIP=1` skips `settings.local.php`, and `ahoy cli` forwards it, so `DRUPAL_SETTINGS_LOCAL_SKIP=1 ahoy drush php:script /app/.artifacts/tmp/debug-errors/settings.php` shows the settings without the local overrides.
