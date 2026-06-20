<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class DownloadDbContainerRegistryTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY', 'docker.io');
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER', 'testuser');
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS', 'testpass');
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE', 'myorg/mydb');
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_DB_DIR', self::$tmp . '/data');
  }

  public function testMissingUser(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER', '');
    $this->envUnset('VORTEX_CONTAINER_REGISTRY_USER');

    $this->runScriptError('src/download-db-container-registry', 'Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER');
  }

  public function testMissingPass(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS', '');
    $this->envUnset('VORTEX_CONTAINER_REGISTRY_PASS');

    $this->runScriptError('src/download-db-container-registry', 'Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS');
  }

  public function testMissingImage(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE', '');
    $this->envUnset('VORTEX_DB_IMAGE');

    $this->runScriptError('src/download-db-container-registry', 'Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE');
  }

  public function testArchiveExistsAndExpands(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);
    file_put_contents($db_dir . '/db.tar', 'fake-tar-data');

    // Initial inspect: not found. After docker load: found.
    $this->mockShellExecMultiple([
      ['value' => '0'],
      ['value' => '1'],
    ]);

    $this->mockPassthru([
      'cmd' => sprintf('docker load -q --input %s', escapeshellarg($db_dir . '/db.tar')),
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/download-db-container-registry');

    $this->assertStringContainsString('Found archived database container image file', $output);
    $this->assertStringContainsString('Found expanded myorg/mydb image on host.', $output);
    $this->assertStringContainsString('Finished database data container image download.', $output);
  }

  public function testArchiveExistsButNotExpanded(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);
    file_put_contents($db_dir . '/db.tar', 'fake-tar-data');

    // Initial inspect: not found. After docker load: still not found.
    $this->mockShellExecMultiple([
      ['value' => '0'],
      ['value' => '0'],
    ]);

    $this->mockPassthruMultiple([
      [
        'cmd' => sprintf('docker load -q --input %s', escapeshellarg($db_dir . '/db.tar')),
        'result_code' => 0,
      ],
      [
        'cmd' => self::$srcDir . '/login-container-registry',
        'result_code' => 0,
      ],
      [
        'cmd' => 'docker pull ' . escapeshellarg('docker.io/myorg/mydb'),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-container-registry');

    $this->assertStringContainsString('Not found expanded myorg/mydb image on host.', $output);
    $this->assertStringContainsString('Downloading myorg/mydb image from the registry.', $output);
    $this->assertStringContainsString('Finished database data container image download.', $output);
  }

  public function testNoArchivePullFromRegistry(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockShellExec('0');

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/login-container-registry',
        'result_code' => 0,
      ],
      [
        'cmd' => 'docker pull ' . escapeshellarg('docker.io/myorg/mydb'),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-container-registry');

    $this->assertStringContainsString('Downloading myorg/mydb image from the registry.', $output);
    $this->assertStringContainsString('Finished database data container image download.', $output);
  }

  public function testFallbackToBaseImage(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->envSet('VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE_BASE', 'myorg/mydb-base');

    $this->mockShellExec('0');

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/login-container-registry',
        'result_code' => 0,
      ],
      [
        'cmd' => 'docker pull ' . escapeshellarg('docker.io/myorg/mydb-base'),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-container-registry');

    $this->assertStringContainsString('Using base image myorg/mydb-base.', $output);
    $this->assertStringContainsString('Finished database data container image download.', $output);
  }

  public function testImageFoundOnHost(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    // Initial inspect: found on host. Still pulls from registry (no archive).
    $this->mockShellExec('1');

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/login-container-registry',
        'result_code' => 0,
      ],
      [
        'cmd' => 'docker pull ' . escapeshellarg('docker.io/myorg/mydb'),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/download-db-container-registry');

    $this->assertStringContainsString('Found myorg/mydb image on host.', $output);
    $this->assertStringContainsString('Finished database data container image download.', $output);
  }

  public function testPullFails(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockShellExec('0');

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/login-container-registry',
        'result_code' => 0,
      ],
      [
        'cmd' => 'docker pull ' . escapeshellarg('docker.io/myorg/mydb'),
        'result_code' => 1,
      ],
    ]);

    $this->runScriptError('src/download-db-container-registry', 'Failed to pull image');
  }

  public function testLoginFails(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockShellExec('0');

    $this->mockPassthru([
      'cmd' => self::$srcDir . '/login-container-registry',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/download-db-container-registry', 'Failed to login to the container registry');
  }

}
