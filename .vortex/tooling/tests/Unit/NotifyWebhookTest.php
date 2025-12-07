<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify-webhook script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifyWebhookTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_WEBHOOK_PROJECT' => 'test-project',
      'VORTEX_NOTIFY_WEBHOOK_LABEL' => 'main',
      'VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL' => 'https://example.com',
      'VORTEX_NOTIFY_WEBHOOK_LOGIN_URL' => 'https://example.com/login',
      'VORTEX_NOTIFY_WEBHOOK_URL' => 'https://webhook.example.com/endpoint',
      'VORTEX_NOTIFY_WEBHOOK_METHOD' => 'POST',
      'VORTEX_NOTIFY_WEBHOOK_HEADERS' => 'Content-type: application/json',
      'VORTEX_NOTIFY_WEBHOOK_EVENT' => 'post_deployment',
    ]);
  }

  public function testSuccessfulNotificationWithDefaultPayload(): void {
    // Mock successful HTTP request.
    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 200, 'body' => '{"success": true}']
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Started Webhook notification', $output);
    $this->assertStringContainsString('Project            : test-project', $output);
    $this->assertStringContainsString('Deployment         : main', $output);
    $this->assertStringContainsString('Finished Webhook notification', $output);
  }

  public function testSuccessfulNotificationWithCustomPayload(): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_PAYLOAD', '{"custom": "%project%", "info": "%label%"}');

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 200, 'body' => '{"success": true}']
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Finished Webhook notification', $output);
  }

  public function testPreDeploymentEventSkipped(): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_EVENT', 'pre_deployment');
    $this->runScriptEarlyPass('src/notify-webhook', 'Skipping Webhook notification for pre_deployment event');
  }

  #[DataProvider('dataProviderMissingRequiredVariables')]
  public function testMissingRequiredVariables(string $var_name): void {
    $this->envUnset($var_name);
    $this->runScriptError('src/notify-webhook', 'Missing required value for ' . $var_name);
  }

  public static function dataProviderMissingRequiredVariables(): array {
    return [
      'project' => ['VORTEX_NOTIFY_WEBHOOK_PROJECT'],
      'label' => ['VORTEX_NOTIFY_WEBHOOK_LABEL'],
      'environment_url' => ['VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL'],
      'webhook_url' => ['VORTEX_NOTIFY_WEBHOOK_URL'],
    ];
  }

  public function testFallbackToGenericVariables(): void {
    $this->envUnsetMultiple([
      'VORTEX_NOTIFY_WEBHOOK_PROJECT',
      'VORTEX_NOTIFY_WEBHOOK_LABEL',
      'VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL',
      'VORTEX_NOTIFY_WEBHOOK_LOGIN_URL',
      'VORTEX_NOTIFY_WEBHOOK_EVENT',
    ]);

    $this->envSetMultiple([
      'VORTEX_NOTIFY_PROJECT' => 'generic-project',
      'VORTEX_NOTIFY_LABEL' => 'develop',
      'VORTEX_NOTIFY_ENVIRONMENT_URL' => 'https://generic.example.com',
      'VORTEX_NOTIFY_LOGIN_URL' => 'https://generic.example.com/login',
      'VORTEX_NOTIFY_EVENT' => 'post_deployment',
    ]);

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Project            : generic-project', $output);
    $this->assertStringContainsString('Deployment         : develop', $output);
  }

  #[DataProvider('dataProviderHttpMethods')]
  public function testHttpMethods(string $method): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_METHOD', $method);

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => $method],
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Method             : ' . $method, $output);
  }

  public static function dataProviderHttpMethods(): array {
    return [
      'POST' => ['POST'],
      'GET' => ['GET'],
      'PUT' => ['PUT'],
    ];
  }

  public function testMultipleHeaders(): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_HEADERS', 'Content-type: application/json|Authorization: Bearer token123|X-Custom: value');

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Content-type: application/json|Authorization: Bearer token123|X-Custom: value', $output);
  }

  public function testCustomExpectedStatus(): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS', '201');

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 201]
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Expected Status    : 201', $output);
  }

  public function testHttpRequestFailureWrongStatus(): void {
    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 500, 'body' => 'Internal Server Error']
    );

    $this->runScriptError('src/notify-webhook', 'Webhook notification failed. Expected status 200 but got 500.');
  }

  public function testTokenReplacementWithSpecialCharacters(): void {
    $this->envSetMultiple([
      'VORTEX_NOTIFY_WEBHOOK_PROJECT' => 'test "quoted" project',
      'VORTEX_NOTIFY_WEBHOOK_LABEL' => 'feature/special-chars-\n-newline',
      'VORTEX_NOTIFY_WEBHOOK_PAYLOAD' => '{"project":"%project%","label":"%label%"}',
    ]);

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Finished Webhook notification', $output);
  }

  public function testWebhookUrlSanitization(): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_URL', 'https://webhook.example.com/secret/path/with/token');

    $this->mockRequest(
      'https://webhook.example.com/secret/path/with/token',
      ['method' => 'POST'],
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-webhook');

    // Verify domain is shown but path is hidden.
    $this->assertStringContainsString('Webhook URL        : https://webhook.example.com/***', $output);
    $this->assertStringNotContainsString('/secret/path', $output);
  }

  public function testAllTokenReplacements(): void {
    $this->envSet('VORTEX_NOTIFY_WEBHOOK_PAYLOAD', '{"p":"%project%","l":"%label%","t":"%timestamp%","e":"%environment_url%","login":"%login_url%","msg":"%message%"}');

    $this->mockRequest(
      'https://webhook.example.com/endpoint',
      ['method' => 'POST'],
      ['status' => 200]
    );

    $output = $this->runScript('src/notify-webhook');

    $this->assertStringContainsString('Finished Webhook notification', $output);
  }

}
