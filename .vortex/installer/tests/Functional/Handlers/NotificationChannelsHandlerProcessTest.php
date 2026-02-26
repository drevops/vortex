<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NotificationChannels::class)]
class NotificationChannelsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'notification_channels_all' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([
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

      'notification_channels_email_only' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([NotificationChannels::EMAIL], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels_github_only' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([NotificationChannels::GITHUB], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels_jira_only' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([NotificationChannels::JIRA], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels_newrelic_only' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([NotificationChannels::NEWRELIC], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels_slack_only' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([NotificationChannels::SLACK], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
        }),
      ],

      'notification_channels_webhook_only' => [
        static::cw(function (): void {
          Env::put(NotificationChannels::envName(), Converter::toList([NotificationChannels::WEBHOOK], ',', TRUE));
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
        }),
      ],

      'notification_channels_none' => [
        static::cw(fn() => Env::put(NotificationChannels::envName(), ',')),
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
