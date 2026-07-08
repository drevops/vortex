<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "notification_channels" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class NotificationChannels extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $channels = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];

    if (!empty($channels)) {
      Env::writeValueDotenv('VORTEX_NOTIFY_CHANNELS', Converter::toList($channels), $context->directory . '/.env');
    }
    else {
      Env::writeValueDotenv('VORTEX_NOTIFY_CHANNELS', '', $context->directory . '/.env', FALSE);
    }

    $tokens = [];

    if (!in_array('email', $channels, TRUE)) {
      $tokens[] = 'NOTIFICATIONS_EMAIL';
    }

    if (!in_array('slack', $channels, TRUE)) {
      $tokens[] = 'NOTIFICATIONS_SLACK';
    }

    if (!in_array('webhook', $channels, TRUE)) {
      $tokens[] = 'NOTIFICATIONS_WEBHOOK';
    }

    if (!in_array('newrelic', $channels, TRUE)) {
      $tokens[] = 'NOTIFICATIONS_NEWRELIC';
    }

    if (!in_array('jira', $channels, TRUE)) {
      $tokens[] = 'NOTIFICATIONS_JIRA';
    }

    if (!in_array('github', $channels, TRUE)) {
      $tokens[] = 'NOTIFICATIONS_GITHUB';
    }

    foreach ($tokens as $token) {
      File::removeTokenAsync($token);
    }
  }

}
