<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class FetchDbAcquiaTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_FETCH_DB_ACQUIA_KEY' => 'test-key',
      'VORTEX_FETCH_DB_ACQUIA_SECRET' => 'test-secret',
      'VORTEX_FETCH_DB_ACQUIA_APP_NAME' => 'myapp',
      'VORTEX_FETCH_DB_ENVIRONMENT' => 'prod',
      'VORTEX_FETCH_DB_ACQUIA_DB_NAME' => 'mydb',
      'VORTEX_FETCH_DB_ACQUIA_DB_DIR' => self::$tmp . '/data',
      'VORTEX_FETCH_DB_ACQUIA_DB_FILE' => 'db.sql',
      'VORTEX_FETCH_DB_ACQUIA_BACKUP_WAIT_INTERVAL' => '1',
      'VORTEX_FETCH_DB_ACQUIA_BACKUP_MAX_WAIT' => '3',
      'VORTEX_ACLI_PATH' => self::$tmp,
    ]);

    mkdir(self::$tmp . '/data', 0755, TRUE);
  }

  public function testMissingKey(): void {
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_KEY', '');
    $this->envUnset('VORTEX_ACQUIA_KEY');

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Missing required value for VORTEX_FETCH_DB_ACQUIA_KEY');
  }

  public function testMissingSecret(): void {
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_SECRET', '');
    $this->envUnset('VORTEX_ACQUIA_SECRET');

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Missing required value for VORTEX_FETCH_DB_ACQUIA_SECRET');
  }

  public function testMissingEnvironment(): void {
    $this->envSet('VORTEX_FETCH_DB_ENVIRONMENT', '');

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Missing required value for VORTEX_FETCH_DB_ENVIRONMENT');
  }

  public function testMissingDbName(): void {
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_DB_NAME', '');

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Missing required value for VORTEX_FETCH_DB_ACQUIA_DB_NAME');
  }

  public function testApplicationHintNotFound(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([]), 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to find an Acquia application matching "myapp".');
  }

  public function testAutoDiscoverSingleApplication(): void {
    // With no hint and exactly one accessible application, it is auto-selected.
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_APP_NAME', '');
    $this->envUnset('VORTEX_ACQUIA_APP_NAME');

    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([['name' => 'NSWSES', 'uuid' => 'app-uuid-123', 'hosting' => ['id' => 'prod:nswses']]]), 'result_code' => 0],
      ['cmd' => $this->envListCmd(), 'output' => $this->envsJson([['name' => 'prod', 'id' => 'env-id-prod']]), 'result_code' => 0],
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]);

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Auto-discovering the Acquia application.', $output);
    $this->assertStringContainsString('Using application "NSWSES"', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
  }

  public function testAutoDiscoverAmbiguousApplications(): void {
    // With no hint and more than one application, the choice is ambiguous.
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_APP_NAME', '');
    $this->envUnset('VORTEX_ACQUIA_APP_NAME');

    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([['name' => 'One', 'uuid' => 'u1', 'hosting' => ['id' => 'prod:one']], ['name' => 'Two', 'uuid' => 'u2']]), 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to auto-discover a single Acquia application (found 2)');
  }

  public function testApplicationMatchedByMachineName(): void {
    // The hint 'mymachine' matches only the hosting machine name.
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_APP_NAME', 'mymachine');

    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([['name' => 'Human Label', 'uuid' => 'app-uuid-123', 'hosting' => ['id' => 'prod:mymachine']]]), 'result_code' => 0],
      ['cmd' => $this->envListCmd(), 'output' => $this->envsJson([['name' => 'prod', 'id' => 'env-id-prod']]), 'result_code' => 0],
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]);

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Using application "Human Label"', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
  }

  public function testApplicationMatchedByUuid(): void {
    // The hint is the application uuid; the application carries no hosting id.
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_APP_NAME', 'app-uuid-123');

    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([['name' => 'Human Label', 'uuid' => 'app-uuid-123']]), 'result_code' => 0],
      ['cmd' => $this->envListCmd(), 'output' => $this->envsJson([['name' => 'prod', 'id' => 'env-id-prod']]), 'result_code' => 0],
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]);

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Using application "Human Label"', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
  }

  public function testEnvironmentNotFound(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([['name' => 'myapp', 'uuid' => 'app-uuid-123']]), 'result_code' => 0],
      ['cmd' => $this->envListCmd(), 'output' => $this->envsJson([]), 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to find the Acquia environment "prod".');
  }

  public function testNoBackups(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson([]), 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'No completed backups found for database "mydb" in environment "prod".');
  }

  public function testCachedDecompressedFile(): void {
    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]));

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Found existing cached DB file', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
    $this->assertFileExists(self::$tmp . '/data/db.sql');
  }

  public function testCachedGzFile(): void {
    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql.gz', $this->gzipBody('SQL DUMP FROM ACQUIA'));

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]));

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Found existing cached gzipped DB file', $output);
    $this->assertStringContainsString('Expanding', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'SQL DUMP FROM ACQUIA');
    $this->assertFileDoesNotExist(self::$tmp . '/data/mydb_backup_12345.sql.gz');
  }

  public function testDownloadAndDecompress(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
      ['cmd' => $this->backupDownloadCmd('12345'), 'output' => $this->downloadJson('https://acquia-backup.s3.amazonaws.com/backup.sql.gz'), 'result_code' => 0],
    ]));

    $this->mockRequest('https://acquia-backup.s3.amazonaws.com/backup.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('DOWNLOADED SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Discovered the backup download URL.', $output);
    $this->assertStringContainsString('Downloaded the database backup.', $output);
    $this->assertStringContainsString('Expanding', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'DOWNLOADED SQL DUMP');
  }

  public function testDownloadUrlEmpty(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
      ['cmd' => $this->backupDownloadCmd('12345'), 'output' => '{}', 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to discover the download URL for backup "12345".');
  }

  public function testDownloadRequestFails(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
      ['cmd' => $this->backupDownloadCmd('12345'), 'output' => $this->downloadJson('https://acquia-backup.s3.amazonaws.com/backup.sql.gz'), 'result_code' => 0],
    ]));

    $this->mockRequest('https://acquia-backup.s3.amazonaws.com/backup.sql.gz', ['method' => 'GET'], ['status' => 500, 'ok' => FALSE, 'body' => '', 'error' => 'Server error']);

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to download database "mydb" backup "12345"');
  }

  public function testDownloadEmptyFile(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
      ['cmd' => $this->backupDownloadCmd('12345'), 'output' => $this->downloadJson('https://acquia-backup.s3.amazonaws.com/backup.sql.gz'), 'result_code' => 0],
    ]));

    $this->mockRequest('https://acquia-backup.s3.amazonaws.com/backup.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Downloaded file is empty or missing');
  }

  public function testInvalidGzip(): void {
    $invalid_gz = self::$tmp . '/data/mydb_backup_12345.sql.gz';
    file_put_contents($invalid_gz, 'NOT VALID GZIP DATA');

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Downloaded file is not a valid gzip archive');

    // The invalid file is left in place for inspection.
    $this->assertFileExists($invalid_gz);
  }

  public function testEmptyGzipDecompressesToNothing(): void {
    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql.gz', $this->gzipBody(''));

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Downloaded file is not a valid gzip archive');
  }

  public function testFreshBackup(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_FETCH_DB_FRESH', '1');

    file_put_contents(self::$tmp . '/data/mydb_backup_99999.sql.gz', $this->gzipBody('FRESH SQL DUMP'));

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupCreateCmd(), 'output' => '', 'result_code' => 0],
      // Poll: the new backup is already listed as completed.
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['99999']), 'result_code' => 0],
      // Latest-backup lookup after the fresh block.
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['99999']), 'result_code' => 0],
    ]));

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Requested a new database backup.', $output);
    $this->assertStringContainsString('Backup completed.', $output);
    $this->assertStringContainsString('Expanding', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'FRESH SQL DUMP');
  }

  public function testFreshBackupTimeout(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_FETCH_DB_FRESH', '1');

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupCreateCmd(), 'output' => '', 'result_code' => 0],
      // No completed backup ever appears within the max wait window.
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson([]), 'result_code' => 0],
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson([]), 'result_code' => 0],
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson([]), 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Backup creation timed out after 3 seconds.');
  }

  public function testDirectoryCreation(): void {
    $db_dir = self::$tmp . '/new-data-dir';
    $this->envSet('VORTEX_FETCH_DB_ACQUIA_DB_DIR', $db_dir);

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
      ['cmd' => $this->backupDownloadCmd('12345'), 'output' => $this->downloadJson('https://acquia-backup.s3.amazonaws.com/backup.sql.gz'), 'result_code' => 0],
    ]));

    $this->mockRequest('https://acquia-backup.s3.amazonaws.com/backup.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('DOWNLOADED SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Creating directory for database dumps.', $output);
    $this->assertTrue(is_dir($db_dir));
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
  }

  public function testBareArrayBackupList(): void {
    // Some CLI versions return a bare list instead of an '_embedded' envelope.
    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql', 'SQL DUMP');

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => json_encode([['id' => '12345', 'completed_at' => '2026-07-07T00:00:00Z']]) ?: '', 'result_code' => 0],
    ]));

    $output = $this->runScript('src/vortex-fetch-db-acquia');

    $this->assertStringContainsString('Found existing cached DB file', $output);
    $this->assertStringContainsString('Finished database backup download from Acquia.', $output);
  }

  public function testDownloadUrlMalformedResponse(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
      ['cmd' => $this->backupDownloadCmd('12345'), 'output' => 'not-json-at-all', 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to discover the download URL for backup "12345".');
  }

  public function testRenameFailure(): void {
    // A cached decompressed dump goes straight to the rename step.
    file_put_contents(self::$tmp . '/data/mydb_backup_12345.sql', 'SQL DUMP');
    // Make the destination a non-empty directory so the rename cannot succeed.
    mkdir(self::$tmp . '/data/db.sql', 0755, TRUE);
    file_put_contents(self::$tmp . '/data/db.sql/blocker', 'x');

    $this->mockCommandExists();

    $this->mockPassthruMultiple(array_merge($this->discoveryMocks(), [
      ['cmd' => $this->backupListCmd(), 'output' => $this->backupsJson(['12345']), 'result_code' => 0],
    ]));

    $this->runScriptError('src/vortex-fetch-db-acquia', 'Unable to rename file');
  }

  /**
   * The version and application/environment discovery command mocks.
   *
   * @return array<int, array{cmd: string, output: string, result_code: int}>
   *   The ordered passthru mocks shared by the discovery phase.
   */
  protected function discoveryMocks(): array {
    return [
      ['cmd' => $this->versionCmd(), 'output' => 'Acquia CLI 2.61.3', 'result_code' => 0],
      ['cmd' => $this->appsListCmd(), 'output' => $this->appsJson([['name' => 'myapp', 'uuid' => 'app-uuid-123']]), 'result_code' => 0],
      ['cmd' => $this->envListCmd(), 'output' => $this->envsJson([['name' => 'prod', 'id' => 'env-id-prod']]), 'result_code' => 0],
    ];
  }

  protected function acliHome(): string {
    return self::$tmp . '/acli-home-' . getmypid();
  }

  protected function acliCmd(string $subcommand): string {
    return sprintf('ACLI_HOME=%s ACLI_KEY=%s ACLI_SECRET=%s ACLI_NO_TELEMETRY=1 %s %s --no-interaction 2>&1', escapeshellarg($this->acliHome()), escapeshellarg('test-key'), escapeshellarg('test-secret'), escapeshellarg('acli'), $subcommand);
  }

  protected function versionCmd(): string {
    return $this->acliCmd('--version');
  }

  protected function appsListCmd(): string {
    return $this->acliCmd('api:applications:list');
  }

  protected function envListCmd(string $uuid = 'app-uuid-123'): string {
    return $this->acliCmd('api:applications:environment-list ' . escapeshellarg($uuid));
  }

  protected function backupCreateCmd(string $env = 'env-id-prod', string $db = 'mydb'): string {
    return $this->acliCmd('api:environments:database-backup-create ' . escapeshellarg($env) . ' ' . escapeshellarg($db));
  }

  protected function backupListCmd(string $env = 'env-id-prod', string $db = 'mydb'): string {
    return $this->acliCmd('api:environments:database-backup-list ' . escapeshellarg($env) . ' ' . escapeshellarg($db));
  }

  protected function backupDownloadCmd(string $id, string $env = 'env-id-prod', string $db = 'mydb'): string {
    return $this->acliCmd('api:environments:database-backup-download ' . escapeshellarg($env) . ' ' . escapeshellarg($db) . ' ' . escapeshellarg($id));
  }

  /**
   * Encodes an application list response.
   *
   * @param array<int, array<string, mixed>> $apps
   *   The application records.
   */
  protected function appsJson(array $apps): string {
    return json_encode(['_embedded' => ['items' => $apps]]) ?: '';
  }

  /**
   * Encodes an environment list response.
   *
   * @param array<int, array<string, string>> $envs
   *   The environment records.
   */
  protected function envsJson(array $envs): string {
    return json_encode(['_embedded' => ['items' => $envs]]) ?: '';
  }

  /**
   * Encodes a backup list response from completed backup ids.
   *
   * @param array<int, string> $ids
   *   The completed backup ids.
   */
  protected function backupsJson(array $ids): string {
    $items = array_map(fn(string $id): array => ['id' => $id, 'completed_at' => '2026-07-07T00:00:00Z'], $ids);

    return json_encode(['_embedded' => ['items' => $items]]) ?: '';
  }

  protected function downloadJson(string $url): string {
    return json_encode(['url' => $url]) ?: '';
  }

  protected function gzipBody(string $content): string {
    return gzencode($content) ?: '';
  }

}
