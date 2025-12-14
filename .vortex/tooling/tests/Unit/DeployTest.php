<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('deploy')]
#[RunTestsInSeparateProcesses]
class DeployTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      // String-based variables.
      'VORTEX_DEPLOY_BRANCH' => '',
      'VORTEX_DEPLOY_PR' => '',
      'VORTEX_DEPLOY_SKIP_BRANCHES' => '',
      'VORTEX_DEPLOY_SKIP_PRS' => '',
      // Boolean-based variables.
      'VORTEX_DEPLOY_SKIP' => '0',
      'VORTEX_DEPLOY_ALLOW_SKIP' => '0',
    ]);
  }

  public function testMissingDeployTypes(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('[FAIL] Missing required value for VORTEX_DEPLOY_TYPES', $output);
  }

  public function testSkipAllDeployments(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_TYPES' => 'webhook,artifact',
      'VORTEX_DEPLOY_SKIP' => '1',
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Found flag to skip all deployments.', $output);
    $this->assertStringContainsString('[ OK ] Skipping deployment webhook,artifact.', $output);
  }

  public function testSkipSpecificPr(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_ALLOW_SKIP' => '1',
      'VORTEX_DEPLOY_PR' => '123',
      'VORTEX_DEPLOY_SKIP_PRS' => '123,456,789',
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Found flag to skip a deployment.', $output);
    $this->assertStringContainsString('Found PR 123 in skip list.', $output);
    $this->assertStringContainsString('Skipping deployment webhook.', $output);
  }

  public function testSkipSpecificBranch(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_ALLOW_SKIP' => '1',
      'VORTEX_DEPLOY_BRANCH' => 'feature/test',
      'VORTEX_DEPLOY_SKIP_BRANCHES' => 'feature/test,hotfix/urgent',
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Found flag to skip a deployment.', $output);
    $this->assertStringContainsString('Found branch feature/test in skip list.', $output);
    $this->assertStringContainsString('Skipping deployment webhook.', $output);
  }

  public function testDeployArtifactOnly(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'artifact');

    $this->mockPassthru([
      'cmd' => $this->getDeployArtifactPath(),
      'output' => 'Artifact deployed successfully',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Artifact deployed successfully', $output);
    $this->assertStringContainsString('[ OK ] Finished deployment.', $output);
  }

  public function testDeployWebhookOnly(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'webhook');

    $this->mockPassthru([
      'cmd' => $this->getDeployWebhookPath(),
      'output' => 'Webhook deployed successfully',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Webhook deployed successfully', $output);
    $this->assertStringContainsString('[ OK ] Finished deployment.', $output);
  }

  public function testDeployContainerRegistryOnly(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'container_registry');

    $this->mockPassthru([
      'cmd' => $this->getDeployContainerRegistryPath(),
      'output' => 'Container registry deployed successfully',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Container registry deployed successfully', $output);
    $this->assertStringContainsString('[ OK ] Finished deployment.', $output);
  }

  public function testDeployLagoonOnly(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'lagoon');

    $this->mockPassthru([
      'cmd' => $this->getDeployLagoonPath(),
      'output' => 'Lagoon deployed successfully',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Lagoon deployed successfully', $output);
    $this->assertStringContainsString('[ OK ] Finished deployment.', $output);
  }

  public function testDeployMultipleTypes(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'artifact,webhook,container_registry');

    $this->mockPassthru([
      'cmd' => $this->getDeployArtifactPath(),
      'output' => 'Artifact deployed',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getDeployWebhookPath(),
      'output' => 'Webhook deployed',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getDeployContainerRegistryPath(),
      'output' => 'Container registry deployed',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Artifact deployed', $output);
    $this->assertStringContainsString('Webhook deployed', $output);
    $this->assertStringContainsString('Container registry deployed', $output);
    $this->assertStringContainsString('[ OK ] Finished deployment.', $output);
  }

  public function testDeployArtifactWithTagMode(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_TYPES' => 'artifact',
      'VORTEX_DEPLOY_MODE' => 'tag',
    ]);

    $this->mockPassthru([
      'cmd' => $this->getDeployArtifactPath(),
      'output' => 'Artifact deployed with tag mode',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Artifact deployed with tag mode', $output);
    $this->assertStringContainsString('[ OK ] Finished deployment.', $output);

    // Verify that the environment variable was set for the artifact script.
    $this->assertEquals('deployment/[tags:-]', getenv('VORTEX_DEPLOY_ARTIFACT_DST_BRANCH'));
  }

  public function testDeployArtifactFailure(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'artifact');

    $this->mockPassthru([
      'cmd' => $this->getDeployArtifactPath(),
      'output' => 'Artifact deployment failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Artifact deployment failed', $output);
  }

  public function testDeployWebhookFailure(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'webhook');

    $this->mockPassthru([
      'cmd' => $this->getDeployWebhookPath(),
      'output' => 'Webhook deployment failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Webhook deployment failed', $output);
  }

  public function testDeployContainerRegistryFailure(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'container_registry');

    $this->mockPassthru([
      'cmd' => $this->getDeployContainerRegistryPath(),
      'output' => 'Container registry deployment failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Container registry deployment failed', $output);
  }

  public function testDeployLagoonFailure(): void {
    $this->envSet('VORTEX_DEPLOY_TYPES', 'lagoon');

    $this->mockPassthru([
      'cmd' => $this->getDeployLagoonPath(),
      'output' => 'Lagoon deployment failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('src/deploy');

    $this->assertStringContainsString('[INFO] Started deployment.', $output);
    $this->assertStringContainsString('Lagoon deployment failed', $output);
  }

  protected function getDeployArtifactPath(): string {
    return (string) realpath(__DIR__ . '/../../src/deploy-artifact');
  }

  protected function getDeployWebhookPath(): string {
    return (string) realpath(__DIR__ . '/../../src/deploy-webhook');
  }

  protected function getDeployContainerRegistryPath(): string {
    return (string) realpath(__DIR__ . '/../../src/deploy-container-registry');
  }

  protected function getDeployLagoonPath(): string {
    return (string) realpath(__DIR__ . '/../../src/deploy-lagoon');
  }

}
