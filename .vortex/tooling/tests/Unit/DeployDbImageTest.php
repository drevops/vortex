<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class DeployDbImageTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
    $this->envUnset('VORTEX_DB_IMAGE');
    $this->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED', '1');
  }

  public function testSkipWhenDeploymentNotRequested(): void {
    $this->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED', '0');

    $this->runScriptEarlyPass('src/deploy-db-image', 'Skipped database container image deployment');
  }

  public function testFailureWhenImageNotSpecified(): void {
    $this->envSet('VORTEX_EXPORT_DB_IMAGE', '');

    $this->runScriptError('src/deploy-db-image', 'Container image name is not specified');
  }

  public function testSuccessfulDeployment(): void {
    $this->mockPassthru([
      'cmd' => self::$srcDir . '/deploy-container-registry',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-db-image');

    $this->assertStringContainsString('Started database container image deployment.', $output);
    $this->assertStringContainsString('Finished database container image deployment.', $output);
  }

  public function testFailureWhenDeploymentFails(): void {
    $this->mockPassthru([
      'cmd' => self::$srcDir . '/deploy-container-registry',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/deploy-db-image', 'Failed to deploy database container image');
  }

}
