<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifyRouterTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_PROJECT' => 'test-project',
      'VORTEX_NOTIFY_LABEL' => 'main',
      'VORTEX_NOTIFY_ENVIRONMENT_URL' => 'https://example.com',
    ]);
  }

  public function testSkipWhenNotifySkipIsSet(): void {
    $this->envSet('VORTEX_NOTIFY_SKIP', '1');

    $this->runScriptEarlyPass('src/notify', 'Skipping dispatching notifications');
  }

  public function testSkipWhenNoChannelsSpecified(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', ',,,');

    $this->runScriptEarlyPass('src/notify', 'No notification channels specified');
  }

  public function testFailureWhenLabelIsMissing(): void {
    $this->envUnset('VORTEX_NOTIFY_LABEL');

    $this->runScriptError('src/notify', 'Missing required value for VORTEX_NOTIFY_LABEL');
  }

  #[DataProvider('dataProviderFailureWithInvalidEventType')]
  public function testFailureWithInvalidEventType(string $event): void {
    $this->envSet('VORTEX_NOTIFY_EVENT', $event);

    $this->runScriptError('src/notify', 'Unsupported event ' . $event . ' provided');
  }

  public static function dataProviderFailureWithInvalidEventType(): array {
    return [
      'invalid event' => ['invalid_event'],
      'deployment' => ['deployment'],
      'pre-deployment' => ['pre-deployment'],
      'postdeployment' => ['postdeployment'],
    ];
  }

  public function testFailureWhenChannelScriptNotFound(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'nonexistent');

    $this->runScriptError('src/notify', "Notification script for channel 'nonexistent' not found or is not executable");
  }

  public function testFailureWhenChannelScriptNotExecutable(): void {
    $script_path = __DIR__ . '/../../src/notify-test-not-executable';
    file_put_contents($script_path, '#!/usr/bin/env php');
    chmod($script_path, 0644);

    try {
      $this->envSet('VORTEX_NOTIFY_CHANNELS', 'test-not-executable');

      $this->runScriptError('src/notify', "Notification script for channel 'test-not-executable' not found or is not executable");
    }
    finally {
      if (file_exists($script_path)) {
        unlink($script_path);
      }
    }
  }

  public function testFailureWhenChannelScriptExitsFails(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'email');

    $script_path = realpath(__DIR__ . '/../../src/notify-email');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/notify', 'Notification to email failed with exit code 1');
  }

  public function testSuccessfulNotificationWithDefaultChannel(): void {
    $this->envUnset('VORTEX_NOTIFY_CHANNELS');

    $script_path = realpath(__DIR__ . '/../../src/notify-email');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'output' => 'Email notification sent successfully',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Started dispatching notifications', $output);
    $this->assertStringContainsString('Email notification sent successfully', $output);
    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public function testSuccessfulNotificationWithSingleChannel(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'slack');

    $script_path = realpath(__DIR__ . '/../../src/notify-slack');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'output' => 'Slack notification sent successfully',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Started dispatching notifications', $output);
    $this->assertStringContainsString('Slack notification sent successfully', $output);
    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public function testSuccessfulNotificationWithMultipleChannels(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'email,slack,webhook');

    $email_path = realpath(__DIR__ . '/../../src/notify-email');
    $slack_path = realpath(__DIR__ . '/../../src/notify-slack');
    $webhook_path = realpath(__DIR__ . '/../../src/notify-webhook');

    $this->mockPassthru([
      'cmd' => '"' . $email_path . '"',
      'output' => 'Email notification sent',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => '"' . $slack_path . '"',
      'output' => 'Slack notification sent',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => '"' . $webhook_path . '"',
      'output' => 'Webhook notification sent',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Started dispatching notifications', $output);
    $this->assertStringContainsString('Email notification sent', $output);
    $this->assertStringContainsString('Slack notification sent', $output);
    $this->assertStringContainsString('Webhook notification sent', $output);
    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public function testSuccessfulNotificationWithChannelsContainingSpaces(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', ' email , slack , webhook ');

    $email_path = realpath(__DIR__ . '/../../src/notify-email');
    $slack_path = realpath(__DIR__ . '/../../src/notify-slack');
    $webhook_path = realpath(__DIR__ . '/../../src/notify-webhook');

    $this->mockPassthru([
      'cmd' => '"' . $email_path . '"',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => '"' . $slack_path . '"',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => '"' . $webhook_path . '"',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  #[DataProvider('dataProviderSuccessfulNotificationWithValidEventTypes')]
  public function testSuccessfulNotificationWithValidEventTypes(string $event): void {
    $this->envSetMultiple([
      'VORTEX_NOTIFY_CHANNELS' => 'email',
      'VORTEX_NOTIFY_EVENT' => $event,
    ]);

    $script_path = realpath(__DIR__ . '/../../src/notify-email');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public static function dataProviderSuccessfulNotificationWithValidEventTypes(): array {
    return [
      'pre_deployment' => ['pre_deployment'],
      'post_deployment' => ['post_deployment'],
    ];
  }

  public function testDefaultEventTypeIsPostDeployment(): void {
    $this->envUnset('VORTEX_NOTIFY_EVENT');
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'email');

    $script_path = realpath(__DIR__ . '/../../src/notify-email');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public function testProjectFallbackToGenericVariable(): void {
    $this->envUnset('VORTEX_NOTIFY_PROJECT');
    $this->envSet('VORTEX_PROJECT', 'fallback-project');
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'email');

    $script_path = realpath(__DIR__ . '/../../src/notify-email');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public function testDefaultLoginUrlGeneration(): void {
    $this->envUnset('VORTEX_NOTIFY_LOGIN_URL');
    $this->envSet('VORTEX_NOTIFY_ENVIRONMENT_URL', 'https://example.com');
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'email');

    $script_path = realpath(__DIR__ . '/../../src/notify-email');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

  public function testAllChannelsExecutedEvenIfOneHasNoOutput(): void {
    $this->envSet('VORTEX_NOTIFY_CHANNELS', 'email,slack');

    $email_path = realpath(__DIR__ . '/../../src/notify-email');
    $slack_path = realpath(__DIR__ . '/../../src/notify-slack');

    $this->mockPassthru([
      'cmd' => '"' . $email_path . '"',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => '"' . $slack_path . '"',
      'output' => 'Slack notification sent',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/notify');

    $this->assertStringContainsString('Started dispatching notifications', $output);
    $this->assertStringContainsString('Slack notification sent', $output);
    $this->assertStringContainsString('Finished dispatching notifications', $output);
  }

}
