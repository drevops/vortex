<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class ExportDbImageTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
    $this->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY', 'docker.io');
    $this->envSet('VORTEX_EXPORT_DB_SERVICE_NAME', 'database');
    $this->envSet('VORTEX_EXPORT_DB_IMAGE_DIR', self::$tmp . '/data');

    // Reset argv.
    $GLOBALS['argv'] = ['export-db-image'];
  }

  public function testMissingImage(): void {
    $this->envSet('VORTEX_EXPORT_DB_IMAGE', '');

    $this->runScriptError('src/export-db-image', 'Missing required value for VORTEX_EXPORT_DB_IMAGE');
  }

  public function testSuccessWithCustomFilename(): void {
    $image_dir = self::$tmp . '/data';
    mkdir($image_dir, 0755, TRUE);

    $GLOBALS['argv'] = ['export-db-image', 'myarchive.sql'];

    $this->mockSleep();

    // Docker compose ps -q.
    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      // Docker commit.
      ['value' => 'sha256:def456'],
    ]);

    $archive_file = $image_dir . '/myarchive.tar';

    $this->mockPassthruMultiple([
      // Lock tables.
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      // Unlock tables.
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('UNLOCK TABLES;')),
        'result_code' => 0,
      ],
      // Upgrade.
      [
        'cmd' => sprintf('docker compose exec -T %s sh -c %s', escapeshellarg('database'), escapeshellarg('mariadb-upgrade --force || mariadb-upgrade --force')),
        'result_code' => 0,
      ],
      // Lock tables after upgrade.
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      // Docker save.
      [
        'cmd' => sprintf('docker save -o %s %s', escapeshellarg($archive_file), escapeshellarg('docker.io/myorg/mydb')),
        'result_code' => 0,
      ],
    ]);

    // Simulate docker save creating the file.
    file_put_contents($archive_file, 'tar-content');

    $output = $this->runScript('src/export-db-image');

    $this->assertStringContainsString('Started database data container image export.', $output);
    $this->assertStringContainsString('Committing exported container image with name docker.io/myorg/mydb.', $output);
    $this->assertStringContainsString('Exporting database image archive to file', $output);
    $this->assertStringContainsString('myarchive.tar', $output);
    $this->assertStringContainsString('Finished database data container image export.', $output);
  }

  public function testSuccessWithDefaultTimestamp(): void {
    $image_dir = self::$tmp . '/data';
    mkdir($image_dir, 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101_120000');

    $this->mockSleep();

    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    $archive_file = $image_dir . '/export_db_20240101_120000.tar';

    $this->mockPassthruMultiple([
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('UNLOCK TABLES;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s sh -c %s', escapeshellarg('database'), escapeshellarg('mariadb-upgrade --force || mariadb-upgrade --force')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker save -o %s %s', escapeshellarg($archive_file), escapeshellarg('docker.io/myorg/mydb')),
        'result_code' => 0,
      ],
    ]);

    file_put_contents($archive_file, 'tar-content');

    $output = $this->runScript('src/export-db-image');

    $this->assertStringContainsString('export_db_20240101_120000.tar', $output);
    $this->assertStringContainsString('Finished database data container image export.', $output);
  }

  public function testDockerSaveFails(): void {
    $image_dir = self::$tmp . '/data';
    mkdir($image_dir, 0755, TRUE);

    $GLOBALS['argv'] = ['export-db-image', 'test.sql'];

    $this->mockSleep();

    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    $archive_file = $image_dir . '/test.tar';

    $this->mockPassthruMultiple([
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('UNLOCK TABLES;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s sh -c %s', escapeshellarg('database'), escapeshellarg('mariadb-upgrade --force || mariadb-upgrade --force')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker save -o %s %s', escapeshellarg($archive_file), escapeshellarg('docker.io/myorg/mydb')),
        'result_code' => 1,
      ],
    ]);

    $this->runScriptError('src/export-db-image', 'Unable to save database image archive file');
  }

  public function testArchiveEmptyAfterSave(): void {
    $image_dir = self::$tmp . '/data';
    mkdir($image_dir, 0755, TRUE);

    $GLOBALS['argv'] = ['export-db-image', 'test.sql'];

    $this->mockSleep();

    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    $archive_file = $image_dir . '/test.tar';

    $this->mockPassthruMultiple([
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('UNLOCK TABLES;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s sh -c %s', escapeshellarg('database'), escapeshellarg('mariadb-upgrade --force || mariadb-upgrade --force')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker save -o %s %s', escapeshellarg($archive_file), escapeshellarg('docker.io/myorg/mydb')),
        'result_code' => 0,
      ],
    ]);

    // Create empty file to simulate failed save.
    file_put_contents($archive_file, '');

    $this->runScriptError('src/export-db-image', 'Unable to save database image archive file');
  }

  public function testExistingArchiveRemoved(): void {
    $image_dir = self::$tmp . '/data';
    mkdir($image_dir, 0755, TRUE);

    $GLOBALS['argv'] = ['export-db-image', 'existing.sql'];

    $this->mockSleep();

    $this->mockShellExecMultiple([
      ['value' => 'abc123'],
      ['value' => 'sha256:def456'],
    ]);

    $archive_file = $image_dir . '/existing.tar';
    // Pre-create the file that should be removed.
    file_put_contents($archive_file, 'old-content');

    $this->mockPassthruMultiple([
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('UNLOCK TABLES;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s sh -c %s', escapeshellarg('database'), escapeshellarg('mariadb-upgrade --force || mariadb-upgrade --force')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker compose exec -T %s mysql -e %s', escapeshellarg('database'), escapeshellarg('FLUSH TABLES WITH READ LOCK;')),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf('docker save -o %s %s', escapeshellarg($archive_file), escapeshellarg('docker.io/myorg/mydb')),
        'result_code' => 0,
      ],
    ]);

    // Simulate new content being written by docker save.
    file_put_contents($archive_file, 'new-tar-content');

    $output = $this->runScript('src/export-db-image');

    $this->assertStringContainsString('Finished database data container image export.', $output);
  }

}
