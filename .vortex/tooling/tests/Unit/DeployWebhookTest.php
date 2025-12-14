<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('deploy')]
#[RunTestsInSeparateProcesses]
class DeployWebhookTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://example.com/webhook',
      'VORTEX_DEPLOY_WEBHOOK_METHOD' => 'GET',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
    ]);
  }

  public function testSuccessfulWebhookCallGet(): void {
    $this->mockRequest('https://example.com/webhook', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE]);

    $output = $this->runScript('src/deploy-webhook');

    $this->assertStringContainsString('Started WEBHOOK deployment.', $output);
    $this->assertStringContainsString('Webhook call completed.', $output);
    $this->assertStringContainsString('Finished WEBHOOK deployment.', $output);
  }

  public function testSuccessfulWebhookCallPost(): void {
    $this->envSet('VORTEX_DEPLOY_WEBHOOK_METHOD', 'POST');

    $this->mockRequest('https://example.com/webhook', ['method' => 'POST'], ['status' => 200, 'ok' => TRUE]);

    $output = $this->runScript('src/deploy-webhook');

    $this->assertStringContainsString('Started WEBHOOK deployment.', $output);
    $this->assertStringContainsString('Webhook call completed.', $output);
    $this->assertStringContainsString('Finished WEBHOOK deployment.', $output);
  }

  public function testSuccessfulWebhookCallPut(): void {
    $this->envSet('VORTEX_DEPLOY_WEBHOOK_METHOD', 'PUT');

    $this->mockRequest('https://example.com/webhook', ['method' => 'PUT'], ['status' => 200, 'ok' => TRUE]);

    $output = $this->runScript('src/deploy-webhook');

    $this->assertStringContainsString('Started WEBHOOK deployment.', $output);
    $this->assertStringContainsString('Webhook call completed.', $output);
    $this->assertStringContainsString('Finished WEBHOOK deployment.', $output);
  }

  public function testCustomExpectedStatusCode(): void {
    $this->envSet('VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS', '201');

    $this->mockRequest('https://example.com/webhook', ['method' => 'GET'], ['status' => 201, 'ok' => TRUE]);

    $output = $this->runScript('src/deploy-webhook');

    $this->assertStringContainsString('Started WEBHOOK deployment.', $output);
    $this->assertStringContainsString('Webhook call completed.', $output);
    $this->assertStringContainsString('Finished WEBHOOK deployment.', $output);
  }

  public function testMissingRequiredUrl(): void {
    $this->envUnset('VORTEX_DEPLOY_WEBHOOK_URL');

    $this->runScriptError('src/deploy-webhook', 'Missing required value for VORTEX_DEPLOY_WEBHOOK_URL');
  }

  public function testHttpRequestFailure(): void {
    $this->mockRequest('https://example.com/webhook', ['method' => 'GET'], ['status' => 500, 'ok' => FALSE]);

    $this->runScriptError('src/deploy-webhook', 'Unable to complete webhook deployment.');
  }

  public function testUnexpectedResponseStatus(): void {
    $this->mockRequest('https://example.com/webhook', ['method' => 'GET'], ['status' => 201, 'ok' => TRUE]);

    $this->runScriptError('src/deploy-webhook', 'Unable to complete webhook deployment.');
  }

  public function testDefaultValues(): void {
    $this->envUnsetMultiple([
      'VORTEX_DEPLOY_WEBHOOK_METHOD',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS',
    ]);

    $this->mockRequest('https://example.com/webhook', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE]);

    $output = $this->runScript('src/deploy-webhook');

    $this->assertStringContainsString('Started WEBHOOK deployment.', $output);
    $this->assertStringContainsString('Webhook call completed.', $output);
    $this->assertStringContainsString('Finished WEBHOOK deployment.', $output);
  }

}
