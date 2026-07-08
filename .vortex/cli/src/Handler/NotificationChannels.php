<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "notification_channels" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class NotificationChannels extends AbstractFieldHandler implements OptionsInterface {

  const EMAIL = 'email';

  const GITHUB = 'github';

  const JIRA = 'jira';

  const NEWRELIC = 'newrelic';

  const SLACK = 'slack';

  const WEBHOOK = 'webhook';

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

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::EMAIL => 'Email',
      self::GITHUB => 'GitHub',
      self::JIRA => 'JIRA',
      self::NEWRELIC => 'New Relic',
      self::SLACK => 'Slack',
      self::WEBHOOK => 'Webhook',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'notification_channels';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Notification channels';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::MultiSelect;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'One or more notification channels.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return [self::EMAIL];
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 160;
  }

}
