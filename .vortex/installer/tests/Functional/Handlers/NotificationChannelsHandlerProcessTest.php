<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NotificationChannels::class)]
class NotificationChannelsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'notification_channels_all' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [
            NotificationChannels::EMAIL,
            NotificationChannels::GITHUB,
            NotificationChannels::JIRA,
            NotificationChannels::NEWRELIC,
            NotificationChannels::SLACK,
            NotificationChannels::WEBHOOK,
          ];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_email_only' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [NotificationChannels::EMAIL];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_github_only' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [NotificationChannels::GITHUB];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_jira_only' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [NotificationChannels::JIRA];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_newrelic_only' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [NotificationChannels::NEWRELIC];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_slack_only' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [NotificationChannels::SLACK];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_webhook_only' => [
      static::cw(function ($test): void {
          $test->prompts[NotificationChannels::id()] = [NotificationChannels::WEBHOOK];
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
      }),
    ];
    yield 'notification_channels_none' => [
      static::cw(fn($test): array => $test->prompts[NotificationChannels::id()] = []),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
  }

}
