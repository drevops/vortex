<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NotificationChannels::class)]
class NotificationChannelsInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'notification_channels, all' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([
            NotificationChannels::EMAIL,
            NotificationChannels::GITHUB,
            NotificationChannels::JIRA,
            NotificationChannels::NEWRELIC,
            NotificationChannels::SLACK,
            NotificationChannels::WEBHOOK,
          ], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels, email only' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([NotificationChannels::EMAIL], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels, github only' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([NotificationChannels::GITHUB], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels, jira only' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([NotificationChannels::JIRA], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels, newrelic only' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([NotificationChannels::NEWRELIC], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels, slack only' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([NotificationChannels::SLACK], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels, webhook only' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(NotificationChannels::id()), Converter::toList([NotificationChannels::WEBHOOK], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
        }),
      ],

      'notification_channels, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(NotificationChannels::id()), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],
    ];
  }

}
