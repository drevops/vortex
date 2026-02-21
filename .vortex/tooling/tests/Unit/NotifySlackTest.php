<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify-slack script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifySlackTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_SLACK_PROJECT' => 'test-project',
      'VORTEX_NOTIFY_SLACK_LABEL' => 'main',
      'VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL' => 'https://example.com',
      'VORTEX_NOTIFY_SLACK_LOGIN_URL' => 'https://example.com/login',
      'VORTEX_NOTIFY_SLACK_WEBHOOK' => 'https://hooks.slack.com/services/T00/B00/XXXX',
      'VORTEX_NOTIFY_SLACK_EVENT' => 'post_deployment',
    ]);
  }

  public function testSuccessfulNotificationPostDeployment(): void {
    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(function ($body): true {
        /** @var array {username: string, icon_emoji: string, attachments: array} $payload */
        $payload = json_decode($body, TRUE);
        // Verify payload structure.
        $this->assertEquals('Deployment Bot', $payload['username']);
        $this->assertEquals(':rocket:', $payload['icon_emoji']);
        $this->assertEquals('good', $payload['attachments'][0]['color']);
        $this->assertStringContainsString('Deployment Complete', $payload['attachments'][0]['title']);
        // Verify fields - post-deployment should have all 4 fields.
        $fields = $payload['attachments'][0]['fields'];
        $this->assertCount(4, $fields, 'Post-deployment should have 4 fields');
        $this->assertEquals('Deployment', $fields[0]['title']);
        $this->assertEquals('Environment', $fields[1]['title']);
        $this->assertEquals('Login', $fields[2]['title']);
        $this->assertEquals('Time', $fields[3]['title']);
        $this->assertEquals('test-project', $this->getFieldValue($payload, 'Deployment'));
        return TRUE;
      }),
      ['Content-Type: application/json'],
      10,
      ['status' => 200, 'body' => 'ok']
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Started Slack notification', $output);
    $this->assertStringContainsString('Project        : test-project', $output);
    $this->assertStringContainsString('Deployment     : main', $output);
    $this->assertStringContainsString('Finished Slack notification', $output);
  }

  public function testSuccessfulNotificationPreDeployment(): void {
    $this->envSet('VORTEX_NOTIFY_SLACK_EVENT', 'pre_deployment');

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(function ($body): true {
        /** @var array {username: string, icon_emoji: string, attachments: array} $payload */
        $payload = json_decode($body, TRUE);
        // Verify pre-deployment styling.
        $this->assertEquals('#808080', $payload['attachments'][0]['color']);
        $this->assertStringContainsString('Deployment Starting', $payload['attachments'][0]['title']);
        // Verify fields - should only have Deployment and Time, not Environment
        // or Login.
        $fields = $payload['attachments'][0]['fields'];
        $this->assertCount(2, $fields, 'Pre-deployment should only have 2 fields');
        $this->assertEquals('Deployment', $fields[0]['title']);
        $this->assertEquals('Time', $fields[1]['title']);
        return TRUE;
      }),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Event          : Deployment Starting', $output);
    $this->assertStringContainsString('Finished Slack notification', $output);
  }

  public function testSuccessfulNotificationWithCustomMessage(): void {
    $this->envSet('VORTEX_NOTIFY_SLACK_MESSAGE', 'Custom deployment of %project% to %label%');

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(function ($body): true {
        /** @var array {username: string, icon_emoji: string, attachments: array} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertStringContainsString('Custom deployment of test-project to main', $payload['attachments'][0]['fallback']);
        return TRUE;
      }),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Finished Slack notification', $output);
  }

  public function testSuccessfulNotificationWithChannel(): void {
    $this->envSet('VORTEX_NOTIFY_SLACK_CHANNEL', '#deployments');

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(function ($body): true {
        /** @var array {username: string, icon_emoji: string, attachments: array, channel: string} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('#deployments', $payload['channel']);
        return TRUE;
      }),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Channel        : #deployments', $output);
    $this->assertStringContainsString('Finished Slack notification', $output);
  }

  public function testSuccessfulNotificationWithCustomBot(): void {
    $this->envSetMultiple([
      'VORTEX_NOTIFY_SLACK_USERNAME' => 'Custom Bot',
      'VORTEX_NOTIFY_SLACK_ICON_EMOJI' => ':robot_face:',
    ]);

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(function ($body): true {
        /** @var array {username: string, icon_emoji: string, attachments: array} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('Custom Bot', $payload['username']);
        $this->assertEquals(':robot_face:', $payload['icon_emoji']);
        return TRUE;
      }),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Username       : Custom Bot', $output);
    $this->assertStringContainsString('Finished Slack notification', $output);
  }

  #[DataProvider('dataProviderMissingRequiredVariables')]
  public function testMissingRequiredVariables(string $var_name): void {
    $this->envUnset($var_name);
    $this->runScriptError('src/notify-slack', 'Missing required value for ' . $var_name);
  }

  public static function dataProviderMissingRequiredVariables(): array {
    return [
      'project' => ['VORTEX_NOTIFY_SLACK_PROJECT'],
      'label' => ['VORTEX_NOTIFY_SLACK_LABEL'],
      'environment_url' => ['VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL'],
      'webhook' => ['VORTEX_NOTIFY_SLACK_WEBHOOK'],
    ];
  }

  public function testFallbackToGenericVariables(): void {
    $this->envUnsetMultiple([
      'VORTEX_NOTIFY_SLACK_PROJECT',
      'VORTEX_NOTIFY_SLACK_LABEL',
      'VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL',
      'VORTEX_NOTIFY_SLACK_LOGIN_URL',
      'VORTEX_NOTIFY_SLACK_EVENT',
    ]);

    $this->envSetMultiple([
      'VORTEX_NOTIFY_PROJECT' => 'generic-project',
      'VORTEX_NOTIFY_LABEL' => 'develop',
      'VORTEX_NOTIFY_ENVIRONMENT_URL' => 'https://generic.example.com',
      'VORTEX_NOTIFY_LOGIN_URL' => 'https://generic.example.com/login',
      'VORTEX_NOTIFY_EVENT' => 'post_deployment',
    ]);

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(fn(): true => TRUE),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Project        : generic-project', $output);
    $this->assertStringContainsString('Deployment     : develop', $output);
  }

  public function testHttpRequestFailureWrongStatus(): void {
    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(fn(): true => TRUE),
      ['Content-Type: application/json'],
      10,
      ['status' => 500, 'body' => 'Internal Server Error']
    );

    $this->runScriptError('src/notify-slack', 'Unable to send notification to Slack');
  }

  public function testWebhookUrlSanitization(): void {
    $this->envSet('VORTEX_NOTIFY_SLACK_WEBHOOK', 'https://hooks.slack.com/services/T00/B00/SECRET');

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/SECRET',
      $this->callback(fn(): true => TRUE),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    // Verify domain is shown but path is hidden.
    $this->assertStringContainsString('Webhook        : https://hooks.slack.com/***', $output);
    $this->assertStringNotContainsString('SECRET', $output);
  }

  public function testTokenReplacementInMessage(): void {
    $this->envSet('VORTEX_NOTIFY_SLACK_MESSAGE', '%project% deployed to %label% at %timestamp%');

    $this->mockRequestPost(
      'https://hooks.slack.com/services/T00/B00/XXXX',
      $this->callback(function ($body): true {
        /** @var array{deployment: array{revision: string, user: string}, attachments: list<array{fallback: string}>} $payload */
        $payload = json_decode($body, TRUE);
        $fallback = $payload['attachments'][0]['fallback'];
        $this->assertStringContainsString('test-project deployed to main at', $fallback);
        // Verify timestamp pattern (dd/mm/yyyy HH:MM:SS TZ).
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2} [A-Z]{3}/', $fallback);
        return TRUE;
      }),
      ['Content-Type: application/json'],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-slack');

    $this->assertStringContainsString('Finished Slack notification', $output);
  }

  protected function getFieldValue(array $payload, string $title): ?string {
    foreach ($payload['attachments'][0]['fields'] as $field) {
      if ($field['title'] === $title) {
        return $field['value'];
      }
    }
    return NULL;
  }

}
