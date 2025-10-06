<?php

/**
 * @file
 * GitHub Actions continuous integration provider settings.
 *
 * Do not place any custom settings in this file.
 * It is used to explicitly map provider environments to
 * $settings['environment'] and set platform-specific settings only.
 * Instead, use per-module settings files.
 */

declare(strict_types=1);

if (!empty(getenv('CI'))) {
  $settings['environment'] = ENVIRONMENT_CI;
}
