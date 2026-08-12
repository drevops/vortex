<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class NotificationChannels extends AbstractHandler {

  public const EMAIL = 'email';

  public const GITHUB = 'github';

  public const JIRA = 'jira';

  public const NEWRELIC = 'newrelic';

  public const SLACK = 'slack';

  public const WEBHOOK = 'webhook';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Notification channels';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select one or more notification channels.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
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
  public function default(array $responses): null|string|bool|array {
    return [self::EMAIL];
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $channels = Env::getFromDotenv('VORTEX_NOTIFY_CHANNELS', $this->destinationDir);

    if (!empty($channels)) {
      $channels = Converter::fromList($channels);
      sort($channels);
      return $channels;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsArray();
    $t = $this->tmpDir;

    if (!empty($v)) {
      Env::writeValueDotenv('VORTEX_NOTIFY_CHANNELS', Converter::toList($v), $t . '/.env');
    }
    else {
      Env::writeValueDotenv('VORTEX_NOTIFY_CHANNELS', '', $t . '/.env', FALSE);
    }

    $tokens = [];

    if (!in_array(self::EMAIL, $v)) {
      $tokens[] = 'NOTIFICATIONS_EMAIL';
    }

    if (!in_array(self::SLACK, $v)) {
      $tokens[] = 'NOTIFICATIONS_SLACK';
    }

    if (!in_array(self::WEBHOOK, $v)) {
      $tokens[] = 'NOTIFICATIONS_WEBHOOK';
    }

    if (!in_array(self::NEWRELIC, $v)) {
      $tokens[] = 'NOTIFICATIONS_NEWRELIC';
    }

    if (!in_array(self::JIRA, $v)) {
      $tokens[] = 'NOTIFICATIONS_JIRA';
    }

    if (!in_array(self::GITHUB, $v)) {
      $tokens[] = 'NOTIFICATIONS_GITHUB';
    }

    foreach ($tokens as $token) {
      File::removeTokenAsync($token);
    }
  }

}
