<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

/**
 * Handler for notification channels selection.
 */
class NotificationChannels extends AbstractHandler {

  /**
   * Email notification channel.
   */
  public const EMAIL = 'email';

  /**
   * GitHub notification channel.
   */
  public const GITHUB = 'github';

  /**
   * JIRA notification channel.
   */
  public const JIRA = 'jira';

  /**
   * New Relic notification channel.
   */
  public const NEWRELIC = 'newrelic';

  /**
   * Slack notification channel.
   */
  public const SLACK = 'slack';

  /**
   * Webhook notification channel.
   */
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
    $channels = Env::getFromDotenv('VORTEX_NOTIFY_CHANNELS', $this->dstDir);

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
      // If no channels selected, set to empty value.
      Env::writeValueDotenv('VORTEX_NOTIFY_CHANNELS', '', $t . '/.env', FALSE);
    }

    // Build list of tokens to remove based on unselected channels.
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

    // Remove tokens for unselected channels.
    foreach ($tokens as $token) {
      File::removeTokenAsync($token);
    }
  }

}
