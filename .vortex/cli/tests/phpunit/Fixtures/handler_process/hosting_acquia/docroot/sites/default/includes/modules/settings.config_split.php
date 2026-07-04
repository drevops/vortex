<?php

/**
 * @file
 * Config split settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

switch ($settings['environment']) {
  case Environment::STAGE:
    $config['config_split.config_split.stage']['status'] = TRUE;
    break;

  case Environment::DEVELOPMENT:
  case Environment::PREVIEW:
    $config['config_split.config_split.dev']['status'] = TRUE;
    break;

  case Environment::CI:
    $config['config_split.config_split.ci']['status'] = TRUE;
    break;

  case Environment::LOCAL:
    $config['config_split.config_split.local']['status'] = TRUE;
    break;
}
