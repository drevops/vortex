<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class PushDbImageTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
    $this->envUnset('VORTEX_DB_IMAGE');
    $this->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED', '1');
  }

  public function testSkipWhenPushNotRequested(): void {
    $this->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED', '0');

    $this->runScriptEarlyPass('src/vortex-push-db-image', 'Skipped database container image push');
  }

  public function testFailureWhenImageNotSpecified(): void {
    $this->envSet('VORTEX_EXPORT_DB_IMAGE', '');

    $this->runScriptError('src/vortex-push-db-image', 'Container image name is not specified');
  }

  public function testSuccessfulPush(): void {
    $this->mockPassthru([
      'cmd' => self::$srcDir . '/vortex-push-container-registry',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/vortex-push-db-image');

    $this->assertStringContainsString('Started database container image push.', $output);
    $this->assertStringContainsString('Finished database container image push.', $output);
  }

  public function testFailureWhenPushFails(): void {
    $this->mockPassthru([
      'cmd' => self::$srcDir . '/vortex-push-container-registry',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/vortex-push-db-image', 'Failed to push database container image');
  }

}
