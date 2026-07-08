<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "timezone" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Timezone extends AbstractHandler implements OptionsInterface {

  const UTC = 'UTC';

  const TIMEZONES = [
    'UTC',
    'Africa/Johannesburg',
    'America/Chicago',
    'America/Los_Angeles',
    'America/New_York',
    'America/Sao_Paulo',
    'America/Toronto',
    'Asia/Dubai',
    'Asia/Hong_Kong',
    'Asia/Kolkata',
    'Asia/Singapore',
    'Asia/Tokyo',
    'Australia/Melbourne',
    'Australia/Sydney',
    'Europe/Amsterdam',
    'Europe/Berlin',
    'Europe/London',
    'Europe/Madrid',
    'Europe/Paris',
    'Pacific/Auckland',
  ];

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $timezone = is_string($value) ? $value : '';

    Env::writeValueDotenv('TZ', $timezone, $context->directory . '/.env');
    File::replaceContentInFile($context->directory . '/renovate.json', '/"timezone": "[A-Za-z0-9\/_\-+]+",/', sprintf('"timezone": "%s",', $timezone));
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return array_combine(self::TIMEZONES, self::TIMEZONES);
  }

}
