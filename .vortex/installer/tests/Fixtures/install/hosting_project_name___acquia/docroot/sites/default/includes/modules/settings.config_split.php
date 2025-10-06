<?php

/**
 * @file
 * Config split settings.
 */

declare(strict_types=1);

switch ($settings['environment']) {
  case ENVIRONMENT_STAGE:
    $config['config_split.config_split.stage']['status'] = TRUE;
    break;

  case ENVIRONMENT_DEV:
    $config['config_split.config_split.dev']['status'] = TRUE;
    break;

  case ENVIRONMENT_CI:
    $config['config_split.config_split.ci']['status'] = TRUE;
    break;

  case ENVIRONMENT_LOCAL:
    $config['config_split.config_split.local']['status'] = TRUE;
    break;
}
