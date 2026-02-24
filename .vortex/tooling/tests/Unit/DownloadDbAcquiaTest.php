<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class DownloadDbAcquiaTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_KEY', 'test-key');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_SECRET', 'test-secret');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_APP_NAME', 'myapp');
    $this->envSet('VORTEX_DOWNLOAD_DB_ENVIRONMENT', 'prod');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_DB_NAME', 'mydb');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_DB_FILE', 'db.sql');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_BACKUP_WAIT_INTERVAL', '1');
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_BACKUP_MAX_WAIT', '3');
  }

  public function testMissingKey(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_KEY', '');
    $this->envUnset('VORTEX_ACQUIA_KEY');

    $this->runScriptError('src/download-db-acquia', 'Missing required value for VORTEX_DOWNLOAD_DB_ACQUIA_KEY');
  }

  public function testMissingSecret(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_SECRET', '');
    $this->envUnset('VORTEX_ACQUIA_SECRET');

    $this->runScriptError('src/download-db-acquia', 'Missing required value for VORTEX_DOWNLOAD_DB_ACQUIA_SECRET');
  }

  public function testMissingAppName(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_APP_NAME', '');
    $this->envUnset('VORTEX_ACQUIA_APP_NAME');

    $this->runScriptError('src/download-db-acquia', 'Missing required value for VORTEX_DOWNLOAD_DB_ACQUIA_APP_NAME');
  }

  public function testMissingEnvironment(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_ENVIRONMENT', '');

    $this->runScriptError('src/download-db-acquia', 'Missing required value for VORTEX_DOWNLOAD_DB_ENVIRONMENT');
  }

  public function testMissingDbName(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_DB_NAME', '');

    $this->runScriptError('src/download-db-acquia', 'Missing required value for VORTEX_DOWNLOAD_DB_ACQUIA_DB_NAME');
  }

  public function testCachedDecompressedFile(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    // Pre-create the decompressed file with backup_id=12345.
    file_put_contents($db_dir . '/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockRequestMultiple([
      // Token.
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      // App UUID.
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      // Env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      // Backups list.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '12345']]]])],
      ],
    ]);

    $output = $this->runScript('src/download-db-acquia');

    $this->assertStringContainsString('Found existing cached DB file', $output);
    $this->assertStringContainsString('Finished database dump download from Acquia.', $output);
    // The file should be renamed to the final path.
    $this->assertFileExists($db_dir . '/db.sql');
  }

  public function testCachedGzFile(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    // Pre-create the gz file with valid gzip content.
    $gzipped = gzencode('SQL DUMP FROM ACQUIA');
    file_put_contents($db_dir . '/mydb_backup_12345.sql.gz', $gzipped);

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '12345']]]])],
      ],
    ]);

    $output = $this->runScript('src/download-db-acquia');

    $this->assertStringContainsString('Found existing cached gzipped DB file', $output);
    $this->assertStringContainsString('Expanding DB file', $output);
    $this->assertStringContainsString('Finished database dump download from Acquia.', $output);
    // Final renamed file should exist.
    $this->assertFileExists($db_dir . '/db.sql');
    $this->assertEquals('SQL DUMP FROM ACQUIA', file_get_contents($db_dir . '/db.sql'));
    // Gz file should be cleaned up.
    $this->assertFileDoesNotExist($db_dir . '/mydb_backup_12345.sql.gz');
  }

  public function testDownloadRequestFails(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '12345']]]])],
      ],
      // Backup download URL.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups/12345/actions/download',
        'response' => ['body' => json_encode(['url' => 'https://acquia-backup.s3.amazonaws.com/backup.sql.gz'])],
      ],
      // Download request fails.
      [
        'url' => 'https://acquia-backup.s3.amazonaws.com/backup.sql.gz',
        'method' => 'GET',
        'response' => ['ok' => FALSE, 'status' => 500, 'body' => ''],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'Unable to download database mydb');
  }

  public function testInvalidGzip(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    // Pre-create an invalid gz file.
    file_put_contents($db_dir . '/mydb_backup_12345.sql.gz', 'NOT VALID GZIP DATA');

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '12345']]]])],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'Downloaded file is not a valid gzip archive');
  }

  public function testNoBackups(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => []]])],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'No backups found for database');
  }

  public function testBackupUrlEmpty(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '12345']]]])],
      ],
      // Backup download URL returns empty.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups/12345/actions/download',
        'response' => ['body' => json_encode([])],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'Unable to discover backup URL');
  }

  public function testFreshBackup(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_DOWNLOAD_DB_FRESH', '1');

    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);

    // Pre-create the gz file to test decompress path (skip download).
    $gzipped = gzencode('FRESH SQL DUMP');
    file_put_contents($db_dir . '/mydb_backup_99999.sql.gz', $gzipped);

    $this->mockRequestMultiple([
      // Token.
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      // App UUID.
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      // Env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      // Create backup.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/backup-create']]])],
      ],
      // Backup creation polling: status completed.
      [
        'url' => 'https://cloud.acquia.com/api/notifications/backup-create',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
      // List backups (after creation).
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '99999']]]])],
      ],
    ]);

    $output = $this->runScript('src/download-db-acquia');

    $this->assertStringContainsString('Creating new database backup for mydb.', $output);
    $this->assertStringContainsString('Backup completed successfully.', $output);
    $this->assertStringContainsString('Expanding DB file', $output);
    $this->assertStringContainsString('Finished database dump download from Acquia.', $output);
    $this->assertFileExists($db_dir . '/db.sql');
    $this->assertEquals('FRESH SQL DUMP', file_get_contents($db_dir . '/db.sql'));
  }

  public function testFreshBackupTimeout(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_DOWNLOAD_DB_FRESH', '1');

    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      // Create backup.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/backup-create']]])],
      ],
      // Polling: in_progress for all iterations.
      [
        'url' => 'https://cloud.acquia.com/api/notifications/backup-create',
        'response' => ['body' => json_encode(['status' => 'in_progress'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/notifications/backup-create',
        'response' => ['body' => json_encode(['status' => 'in_progress'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/notifications/backup-create',
        'response' => ['body' => json_encode(['status' => 'in_progress'])],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'Backup creation timed out');
  }

  public function testFreshBackupFailed(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_DOWNLOAD_DB_FRESH', '1');

    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      // Create backup.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/backup-create']]])],
      ],
      // Polling: failed.
      [
        'url' => 'https://cloud.acquia.com/api/notifications/backup-create',
        'response' => ['body' => json_encode(['status' => 'failed'])],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'Backup creation failed');
  }

  public function testTokenError(): void {
    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['ok' => FALSE, 'status' => 401, 'body' => ''],
      ],
    ]);

    $this->runScriptError('src/download-db-acquia', 'Unable to retrieve a token');
  }

  public function testDirectoryCreation(): void {
    // Don't pre-create directory - it should be auto-created.
    $db_dir = self::$tmp . '/new-data-dir';
    $this->envSet('VORTEX_DOWNLOAD_DB_ACQUIA_DB_DIR', $db_dir);

    // Pre-create the decompressed file (need directory first).
    mkdir($db_dir, 0755, TRUE);
    file_put_contents($db_dir . '/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/databases/mydb/backups?sort=created',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => '12345']]]])],
      ],
    ]);

    $output = $this->runScript('src/download-db-acquia');

    $this->assertTrue(is_dir($db_dir));
    $this->assertStringContainsString('Finished database dump download from Acquia.', $output);
  }

}
