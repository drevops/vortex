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
    $channels = $this->getResponseAsArray();

    // Build list of tokens to remove based on unselected channels.
    $tokens_to_remove = [];

    if (!in_array(self::EMAIL, $channels)) {
      $tokens_to_remove[] = 'NOTIFICATIONS_EMAIL';
    }

    if (!in_array(self::SLACK, $channels)) {
      $tokens_to_remove[] = 'NOTIFICATIONS_SLACK';
    }

    if (!in_array(self::WEBHOOK, $channels)) {
      $tokens_to_remove[] = 'NOTIFICATIONS_WEBHOOK';
    }

    if (!in_array(self::NEWRELIC, $channels)) {
      $tokens_to_remove[] = 'NOTIFICATIONS_NEWRELIC';
    }

    if (!in_array(self::JIRA, $channels)) {
      $tokens_to_remove[] = 'NOTIFICATIONS_JIRA';
    }

    if (!in_array(self::GITHUB, $channels)) {
      $tokens_to_remove[] = 'NOTIFICATIONS_GITHUB';
    }

    // Remove tokens for unselected channels.
    foreach ($tokens_to_remove as $token_to_remove) {
      File::removeTokenAsync($token_to_remove);
    }

    // Update VORTEX_NOTIFY_CHANNELS variable in .env with selected channels.
    if (!empty($channels)) {
      $channels_list = Converter::toList($channels);
      File::replaceContentInFile($this->tmpDir . '/.env', '/VORTEX_NOTIFY_CHANNELS=.*/', 'VORTEX_NOTIFY_CHANNELS=' . $channels_list);
    }
    else {
      // If no channels selected, comment out the variable.
      File::replaceContentInFile($this->tmpDir . '/.env', '/VORTEX_NOTIFY_CHANNELS=.*/', '# VORTEX_NOTIFY_CHANNELS=');
    }
  }

}
