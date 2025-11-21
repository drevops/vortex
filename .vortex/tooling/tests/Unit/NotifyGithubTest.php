<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify-github script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifyGithubTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_GITHUB_TOKEN' => 'ghp_test123456',
      'VORTEX_NOTIFY_GITHUB_REPOSITORY' => 'owner/repo',
      'VORTEX_NOTIFY_GITHUB_LABEL' => 'main',
      'VORTEX_NOTIFY_GITHUB_EVENT' => 'pre_deployment',
      'VORTEX_NOTIFY_GITHUB_ENVIRONMENT_TYPE' => 'production',
      'VORTEX_NOTIFY_GITHUB_ENVIRONMENT_URL' => 'https://example.com',
    ]);
  }

  public function testSuccessfulPreDeploymentNotification(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'pre_deployment');

    // Mock deployment creation.
    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments',
      $this->callback(function ($body): true {
        /** @var array {ref: string, environment: string, state: string} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('main', $payload['ref']);
        $this->assertEquals('production', $payload['environment']);
        return TRUE;
      }),
      [
        'Authorization: token ghp_test123456',
        'Accept: application/vnd.github.v3+json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": 123456789}']
    );

    $output = $this->runScript('src/notify-github');

    $this->assertStringContainsString('Started GitHub notification for pre_deployment event', $output);
    $this->assertStringContainsString('Repository      : owner/repo', $output);
    $this->assertStringContainsString('Label (ref)     : main', $output);
    $this->assertStringContainsString('Environment Type: production', $output);

    $this->assertStringContainsString('Created deployment with ID 123456789', $output);
    $this->assertStringContainsString('Finished GitHub notification for pre_deployment event', $output);
  }

  public function testSuccessfulPostDeploymentNotification(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'post_deployment');

    // Mock getting deployments.
    $this->mockRequestGet(
      'https://api.github.com/repos/owner/repo/deployments?ref=main',
      [
        'Authorization: token ghp_test123456',
        'Accept: application/vnd.github.v3+json',
      ],
      10,
      ['status' => 200, 'body' => '[{"id": 987654321}]']
    );

    // Mock posting status update.
    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments/987654321/statuses',
      $this->callback(function ($body): true {
        /** @var array {ref: string, environment: string, state: string} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('success', $payload['state']);
        $this->assertEquals('https://example.com', $payload['environment_url']);
        return TRUE;
      }),
      [
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ghp_test123456',
      ],
      10,
      ['status' => 201, 'body' => '{"state": "success"}']
    );

    $output = $this->runScript('src/notify-github');

    $this->assertStringContainsString('Started GitHub notification for post_deployment event', $output);
    $this->assertStringContainsString('Deployment ID      : 987654321', $output);
    $this->assertStringContainsString('Marked deployment as finished', $output);
    $this->assertStringContainsString('Finished GitHub notification for post_deployment event', $output);
  }

  public function testPreDeploymentWithDefaultEnvironmentType(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'pre_deployment');
    $this->envSet('VORTEX_NOTIFY_GITHUB_ENVIRONMENT_TYPE', 'PR');

    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments',
      $this->callback(function ($body): true {
        /** @var array {ref: string, environment: string, state: string} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('PR', $payload['environment']);
        return TRUE;
      }),
      ['Authorization: token ghp_test123456', 'Accept: application/vnd.github.v3+json'],
      10,
      ['status' => 201, 'body' => '{"id": 123456789}']
    );

    $output = $this->runScript('src/notify-github');

    $this->assertStringContainsString('Environment Type: PR', $output);
  }

  public function testPreDeploymentFailureInvalidDeploymentId(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'pre_deployment');

    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments',
      $this->callback(fn(): true => TRUE),
      ['Authorization: token ghp_test123456', 'Accept: application/vnd.github.v3+json'],
      10,
      ['status' => 200, 'body' => '{"id": 123}']
    );

    $this->runScriptError('src/notify-github', 'Failed to get a deployment ID for a pre_deployment event');

  }

  public function testPostDeploymentFailureNoDeploymentFound(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'post_deployment');

    $this->mockRequestGet(
      'https://api.github.com/repos/owner/repo/deployments?ref=main',
      ['Authorization: token ghp_test123456', 'Accept: application/vnd.github.v3+json'],
      10,
      ['status' => 200, 'body' => '[]']
    );

    $this->runScriptError('src/notify-github', 'Failed to find a previous deployment for ref main');
  }

  public function testPostDeploymentFailureStatusUpdateFailed(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'post_deployment');

    $this->mockRequestGet(
      'https://api.github.com/repos/owner/repo/deployments?ref=main',
      ['Authorization: token ghp_test123456', 'Accept: application/vnd.github.v3+json'],
      10,
      ['status' => 200, 'body' => '[{"id": 987654321}]']
    );

    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments/987654321/statuses',
      $this->callback(fn(): true => TRUE),
      ['Accept: application/vnd.github.v3+json', 'Authorization: token ghp_test123456'],
      10,
      ['status' => 200, 'body' => '{"state": "error"}']
    );

    $this->runScriptError('src/notify-github', 'unable to update the deployment status');

  }

  #[DataProvider('dataProviderMissingRequiredVariables')]
  public function testMissingRequiredVariables(string $var_name): void {
    $this->envUnset($var_name);
    $this->runScriptError('src/notify-github', 'Missing required value for ' . $var_name);
  }

  public static function dataProviderMissingRequiredVariables(): array {
    return [
      'token' => ['VORTEX_NOTIFY_GITHUB_TOKEN'],
      'repository' => ['VORTEX_NOTIFY_GITHUB_REPOSITORY'],
      'label' => ['VORTEX_NOTIFY_GITHUB_LABEL'],
      'event' => ['VORTEX_NOTIFY_GITHUB_EVENT'],
    ];
  }

  public function testFallbackToGenericGithubToken(): void {
    $this->envUnsetMultiple([
      'VORTEX_NOTIFY_GITHUB_TOKEN',
      'VORTEX_NOTIFY_GITHUB_LABEL',
    ]);

    $this->envSet('GITHUB_TOKEN', 'ghp_fallback123');
    $this->envSet('VORTEX_NOTIFY_LABEL', 'develop');
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'pre_deployment');

    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments',
      $this->callback(function ($body): true {
        /** @var array {int, array{ref: string, environment: string, state: string}} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('develop', $payload['ref']);
        return TRUE;
      }),
      ['Authorization: token ghp_fallback123', 'Accept: application/vnd.github.v3+json'],
      10,
      ['status' => 201, 'body' => '{"id": 123456789}']
    );

    $output = $this->runScript('src/notify-github');

    $this->assertStringContainsString('Label (ref)     : develop', $output);
    $this->assertStringContainsString('Finished GitHub notification', $output);
  }

  public function testPostDeploymentMissingEnvironmentUrl(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'post_deployment');
    $this->envUnset('VORTEX_NOTIFY_GITHUB_ENVIRONMENT_URL');

    $this->runScriptError('src/notify-github', 'Missing required value for VORTEX_NOTIFY_GITHUB_ENVIRONMENT_URL');
  }

  public function testDeploymentIdValidationEdgeCases(): void {
    $this->envSet('VORTEX_NOTIFY_GITHUB_EVENT', 'pre_deployment');

    // Test with deployment ID that's too short (8 digits).
    $this->mockRequestPost(
      'https://api.github.com/repos/owner/repo/deployments',
      $this->callback(fn(): true => TRUE),
      ['Authorization: token ghp_test123456', 'Accept: application/vnd.github.v3+json'],
      10,
      ['status' => 200, 'body' => '{"id": 12345678}']
    );

    $this->runScriptError('src/notify-github', 'Failed to get a deployment ID');

  }

}
