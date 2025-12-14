<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\Group;

#[Group('deploy')]
class DeployContainerRegistryTest extends UnitTestCase {

  protected function getLoginScriptPath(): string {
    return realpath(__DIR__ . '/../../src') . '/login-container-registry';
  }

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_DEPLOY_CONTAINER_REGISTRY' => 'docker.io',
      'VORTEX_DEPLOY_CONTAINER_REGISTRY_USER' => 'testuser',
      'VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS' => 'testpass',
      'VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP' => 'web=myorg/myapp',
      'VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG' => 'latest',
    ]);
  }

  public function testSuccessfulDeploymentSingleService(): void {
    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec for getting container ID.
    $this->mockShellExecMultiple([
    // Docker compose ps -q.
      ['value' => 'abc123'],
    // Docker commit.
      ['value' => 'sha256:def456'],
    ]);

    // Mock docker push.
    $this->mockPassthru([
      'cmd' => "docker push 'docker.io/myorg/myapp:latest'",
      'output' => 'Pushing image...',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-container-registry');

    $this->assertStringContainsString('Started container registry deployment.', $output);
    $this->assertStringContainsString('Processing service web.', $output);
    $this->assertStringContainsString('Found "web" service container with id "abc123".', $output);
    $this->assertStringContainsString('Committing container image with name "docker.io/myorg/myapp:latest".', $output);
    $this->assertStringContainsString('Committed container image with id "def456".', $output);
    $this->assertStringContainsString('Pushing container image to the registry.', $output);
    $this->assertStringContainsString('Finished container registry deployment.', $output);
  }

  public function testSuccessfulDeploymentMultipleServices(): void {
    $this->envSet('VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP', 'web=myorg/web,db=myorg/db');

    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec for both services.
    $this->mockShellExecMultiple([
    // Docker compose ps -q web.
      ['value' => 'abc123'],
    // Docker commit web.
      ['value' => 'sha256:def456'],
    // Docker compose ps -q db.
      ['value' => 'xyz789'],
    // Docker commit db.
      ['value' => 'sha256:ghi012'],
    ]);

    // Mock docker push for both services.
    $this->mockPassthru([
      'cmd' => "docker push 'docker.io/myorg/web:latest'",
      'output' => 'Pushing web...',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => "docker push 'docker.io/myorg/db:latest'",
      'output' => 'Pushing db...',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-container-registry');

    $this->assertStringContainsString('Processing service web.', $output);
    $this->assertStringContainsString('Processing service db.', $output);
    $this->assertStringContainsString('Finished container registry deployment.', $output);
  }

  public function testImageWithExistingTag(): void {
    $this->envSet('VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP', 'web=myorg/myapp:v1.0');

    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec.
    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    // Mock docker push with custom tag.
    $this->mockPassthru([
      'cmd' => "docker push 'docker.io/myorg/myapp:v1.0'",
      'output' => 'Pushing image...',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-container-registry');

    $this->assertStringContainsString('Committing container image with name "docker.io/myorg/myapp:v1.0".', $output);
  }

  public function testCustomRegistry(): void {
    $this->envSet('VORTEX_DEPLOY_CONTAINER_REGISTRY', 'gcr.io');

    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec.
    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    // Mock docker push.
    $this->mockPassthru([
      'cmd' => "docker push 'gcr.io/myorg/myapp:latest'",
      'output' => 'Pushing image...',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-container-registry');

    $this->assertStringContainsString('Committing container image with name "gcr.io/myorg/myapp:latest".', $output);
  }

  public function testEmptyMap(): void {
    $this->envSet('VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP', '');

    $output = $this->runScript('src/deploy-container-registry', 0);

    $this->assertStringContainsString('Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable.', $output);
    $this->assertStringNotContainsString('Processing service', $output);
  }

  public function testMissingUser(): void {
    $this->envUnsetMultiple(['VORTEX_DEPLOY_CONTAINER_REGISTRY_USER', 'VORTEX_CONTAINER_REGISTRY_USER']);

    $this->runScriptError('src/deploy-container-registry', 'Missing required value for VORTEX_DEPLOY_CONTAINER_REGISTRY_USER, VORTEX_CONTAINER_REGISTRY_USER');
  }

  public function testMissingPassword(): void {
    $this->envUnsetMultiple(['VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS', 'VORTEX_CONTAINER_REGISTRY_PASS']);

    $this->runScriptError('src/deploy-container-registry', 'Missing required value for VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS, VORTEX_CONTAINER_REGISTRY_PASS');
  }

  public function testInvalidMapFormat(): void {
    $this->envSet('VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP', 'invalid-no-equals');

    $this->runScriptError('src/deploy-container-registry', 'invalid key/value pair "invalid-no-equals" provided.');
  }

  public function testServiceNotRunning(): void {
    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec returning empty (service not running).
    $this->mockShellExec('');

    $this->runScriptError('src/deploy-container-registry', 'Service "web" is not running.');
  }

  public function testDockerCommitFailure(): void {
    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec - container ID found but commit returns empty.
    $this->mockShellExecMultiple([
      // Docker compose ps -q returns container ID.
      ['value' => 'abc123'],
      // Docker commit returns empty (failure).
      ['value' => ''],
    ]);

    $this->runScriptError('src/deploy-container-registry', 'Failed to commit container image.');
  }

  public function testLoginFailure(): void {
    // Mock login script failure.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Login failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-container-registry');
  }

  public function testPushFailure(): void {
    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec.
    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    // Mock docker push failure.
    $this->mockPassthru([
      'cmd' => "docker push 'docker.io/myorg/myapp:latest'",
      'output' => 'Push failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-container-registry');
  }

  public function testFallbackToVortexContainerRegistry(): void {
    $this->envUnset('VORTEX_DEPLOY_CONTAINER_REGISTRY');
    $this->envSet('VORTEX_CONTAINER_REGISTRY', 'fallback.registry.io');

    // Mock login script.
    $this->mockPassthru([
      'cmd' => $this->getLoginScriptPath(),
      'output' => 'Logging in...',
      'result_code' => 0,
    ]);

    // Mock shell_exec.
    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    // Mock docker push.
    $this->mockPassthru([
      'cmd' => "docker push 'fallback.registry.io/myorg/myapp:latest'",
      'output' => 'Pushing image...',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-container-registry');

    $this->assertStringContainsString('Committing container image with name "fallback.registry.io/myorg/myapp:latest".', $output);
  }

}
