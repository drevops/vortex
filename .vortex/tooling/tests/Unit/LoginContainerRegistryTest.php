<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('deploy')]
class LoginContainerRegistryTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_CONTAINER_REGISTRY' => 'docker.io',
      'VORTEX_CONTAINER_REGISTRY_USER' => 'testuser',
      'VORTEX_CONTAINER_REGISTRY_PASS' => 'testpass',
      'HOME' => self::$tmp,
    ]);
  }

  public function testSuccessfulLogin(): void {
    $this->mockPassthru([
      'cmd' => "echo 'testpass' | docker login --username 'testuser' --password-stdin 'docker.io'",
      'output' => 'Login Succeeded',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Logging in to registry "docker.io".', $output);
  }

  public function testAlreadyLoggedIn(): void {
    mkdir(self::$tmp . '/.docker');
    file_put_contents(self::$tmp . '/.docker/config.json', '{"auths":{"docker.io":{"auth":"dGVzdA=="}}}');

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Already logged in to the registry "docker.io".', $output);
  }

  public function testMissingCredentialsSkips(): void {
    $this->envUnsetMultiple(['VORTEX_CONTAINER_REGISTRY_USER', 'VORTEX_CONTAINER_REGISTRY_PASS']);

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Skipping login to the container registry', $output);
  }

  public function testMissingUserOnly(): void {
    $this->envUnset('VORTEX_CONTAINER_REGISTRY_USER');

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Skipping login to the container registry', $output);
  }

  public function testMissingPasswordOnly(): void {
    $this->envUnset('VORTEX_CONTAINER_REGISTRY_PASS');

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Skipping login to the container registry', $output);
  }

  public function testEmptyRegistryName(): void {
    $this->envSet('VORTEX_CONTAINER_REGISTRY', '   ');

    $this->mockPassthru([
      'cmd' => "echo 'testpass' | docker login --username 'testuser' --password-stdin '   '",
      'output' => 'Login Succeeded',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Logging in to registry "   ".', $output);
  }

  public function testCustomRegistry(): void {
    $this->envSet('VORTEX_CONTAINER_REGISTRY', 'gcr.io');

    $this->mockPassthru([
      'cmd' => "echo 'testpass' | docker login --username 'testuser' --password-stdin 'gcr.io'",
      'output' => 'Login Succeeded',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/login-container-registry');

    $this->assertStringContainsString('Logging in to registry "gcr.io".', $output);
  }

  public function testLoginFailure(): void {
    $this->mockPassthru([
      'cmd' => "echo 'testpass' | docker login --username 'testuser' --password-stdin 'docker.io'",
      'output' => 'Error: Cannot perform an interactive login from a non TTY device',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/login-container-registry');
  }

}
