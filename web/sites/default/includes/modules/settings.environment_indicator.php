<?php

/**
 * @file
 * Environment indicator settings.
 */

declare(strict_types=1);

$config['environment_indicator.indicator']['name'] = $settings['environment'];
$config['environment_indicator.indicator']['bg_color'] = '#006600';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';
$config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
$config['environment_indicator.settings']['favicon'] = TRUE;

switch ($settings['environment']) {
  case ENVIRONMENT_PROD:
    $config['environment_indicator.indicator']['bg_color'] = '#ef5350';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    break;

  case ENVIRONMENT_TEST:
    $config['environment_indicator.indicator']['bg_color'] = '#fff176';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    break;

  case ENVIRONMENT_DEV:
    $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
    break;
}
