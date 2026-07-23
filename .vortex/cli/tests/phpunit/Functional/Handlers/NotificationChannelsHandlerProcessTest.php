<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class NotificationChannelsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'notification_channels_all' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = [
            'email',
            'github',
            'jira',
            'newrelic',
            'slack',
            'webhook',
          ];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_email_only' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = ['email'];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_github_only' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = ['github'];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_jira_only' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = ['jira'];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_newrelic_only' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = ['newrelic'];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_slack_only' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = ['slack'];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
    yield 'notification_channels_webhook_only' => [
      self::cw(function ($test): void {
          $test->prompts['notification_channels'] = ['webhook'];
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_NOTIFY_WEBHOOK_URL');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
      }),
    ];
    yield 'notification_channels_none' => [
      self::cw(fn($test): array => $test->prompts['notification_channels'] = []),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_FROM');
          $test->assertSutNotContains('VORTEX_NOTIFY_EMAIL_RECIPIENTS');
          $test->assertSutNotContains('VORTEX_NOTIFY_JIRA_USER_EMAIL');
          $test->assertSutNotContains('VORTEX_NOTIFY_WEBHOOK_URL');
      }),
    ];
  }

}
