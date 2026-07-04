<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class FetchDbLagoonTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  /**
   * SSH key file used in assertions.
   */
  protected static string $sshFile = '/home/user/.ssh/id_rsa';

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSetMultiple([
      'VORTEX_FETCH_DB_LAGOON_PROJECT' => 'myproject',
      'VORTEX_FETCH_DB_ENVIRONMENT' => 'main',
      'VORTEX_FETCH_DB_SSH_FILE' => self::$sshFile,
      'VORTEX_FETCH_DB_LAGOON_DB_DIR' => self::$tmp . '/data',
      'VORTEX_FETCH_DB_LAGOON_DB_FILE' => 'db.sql',
    ]);
  }

  public function testMissingProject(): void {
    $this->envUnset('VORTEX_FETCH_DB_LAGOON_PROJECT');

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Missing required value for VORTEX_FETCH_DB_LAGOON_PROJECT, LAGOON_PROJECT');
  }

  public function testMissingLagoonCli(): void {
    $this->mockCommandMissing();

    $this->runScriptError('src/vortex-fetch-db-lagoon', "Command 'lagoon' is not available.");
  }

  public function testSuccess(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'restore created', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => '{"result":"https://storage.example.com/backup-latest.sql"}', 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/backup-latest.sql', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Started database backup download from Lagoon.', $output);
    $this->assertStringContainsString('Discovering "mariadb" backups for environment "main".', $output);
    $this->assertStringContainsString('Selected backup "latest-id"', $output);
    $this->assertStringContainsString('Downloading the database backup.', $output);
    $this->assertStringContainsString('Finished database backup download from Lagoon.', $output);
  }

  public function testSuccessAfterPolling(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();
    $this->mockSleep();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'restore created', 'result_code' => 0],
      // First poll: not ready yet.
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => 'no download file found, status of backups restoration is pending', 'result_code' => 1],
      // Second poll: ready.
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => '{"result":"https://storage.example.com/backup-latest.sql"}', 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/backup-latest.sql', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Waiting for the backup to be retrieved.', $output);
    $this->assertStringContainsString('Finished database backup download from Lagoon.', $output);
  }

  public function testRetrieveAlreadyCreatedIsNonFatal(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      // Retrieval already triggered previously - non-zero but non-fatal.
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'retrieval for latest-id has already been created', 'result_code' => 1],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => '{"result":"https://storage.example.com/backup-latest.sql"}', 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/backup-latest.sql', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Finished database backup download from Lagoon.', $output);
  }

  public function testRetrieveFailure(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'permission denied', 'result_code' => 1],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Failed to request backup retrieval');
  }

  public function testNoBackupsFound(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      // Only a files backup exists, no matching 'mariadb' source.
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => '{"data":[{"backupid":"files-id","source":"nginx","created":"2024-01-03 00:00:00"}]}', 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'No "mariadb" backups found for environment "main".');
  }

  public function testEmptyBackupIdFails(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => '{"data":[{"backupid":"","source":"mariadb","created":"2024-01-01 00:00:00"}]}', 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Unable to determine the latest backup ID.');
  }

  public function testPollTimeout(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->envSet('VORTEX_FETCH_DB_LAGOON_STATUS_RETRIES', '2');
    $this->mockCommandExists();
    $this->mockSleep();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'restore created', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => 'pending', 'result_code' => 1],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => 'pending', 'result_code' => 1],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Timed out waiting for the backup to be retrieved.');
  }

  public function testDownloadFailure(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'restore created', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => '{"result":"https://storage.example.com/backup-latest.sql"}', 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/backup-latest.sql', ['method' => 'GET'], ['status' => 500, 'ok' => FALSE, 'body' => '', 'error' => 'Server error']);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Failed to download the database backup from Lagoon');
  }

  public function testSetupSshFails(): void {
    $this->mockCommandExists();

    $this->mockPassthru(['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 1]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Failed to setup SSH.');
  }

  public function testInContainerSkipsSsh(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->envSet('VORTEX_FETCH_DB_SSH_FILE', 'false');
    $this->mockCommandExists();

    // No vortex-setup-ssh call and no --ssh-key flag in the CLI commands.
    $this->mockPassthruMultiple([
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty", FALSE), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'", FALSE), 'output' => 'restore created', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json", FALSE), 'output' => '{"result":"https://storage.example.com/backup-latest.sql"}', 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/backup-latest.sql', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Finished database backup download from Lagoon.', $output);
  }

  public function testDirectoryCreation(): void {
    $db_dir = self::$tmp . '/new-dir';
    $this->envSet('VORTEX_FETCH_DB_LAGOON_DB_DIR', $db_dir);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list backups --environment 'main' --output-json --pretty"), 'output' => $this->backupsJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("retrieve backup --environment 'main' --backup-id 'latest-id'"), 'output' => 'restore created', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get backup --environment 'main' --backup-id 'latest-id' --output-json"), 'output' => '{"result":"https://storage.example.com/backup-latest.sql"}', 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/backup-latest.sql', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Creating directory for database dumps.', $output);
    $this->assertTrue(is_dir($db_dir));
  }

  /**
   * Builds a backups listing fixture.
   *
   * Contains two DB backups (the latest is selected) and a non-matching files
   * backup that must be filtered out.
   */
  protected function backupsJson(): string {
    return json_encode([
      'data' => [
        ['backupid' => 'old-id', 'source' => 'mariadb', 'created' => '2024-01-01 00:00:00', 'restored' => 'false', 'restorestatus' => ''],
        ['backupid' => 'latest-id', 'source' => 'mariadb', 'created' => '2024-01-02 00:00:00', 'restored' => 'false', 'restorestatus' => ''],
        ['backupid' => 'files-id', 'source' => 'nginx', 'created' => '2024-01-03 00:00:00', 'restored' => 'false', 'restorestatus' => ''],
      ],
    ]) ?: '';
  }

  protected function configCmd(): string {
    return "'lagoon' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'";
  }

  protected function lagoonCmd(string $subcommand, bool $with_ssh = TRUE): string {
    $ssh = $with_ssh ? sprintf(" --ssh-key '%s'", self::$sshFile) : '';
    return sprintf("'lagoon' --force --skip-update-check%s --lagoon 'amazeeio' --project 'myproject' %s 2>&1", $ssh, $subcommand);
  }

  protected function mockCommandMissing(string $namespace = 'DrevOps\\VortexTooling'): void {
    $this->registerMock('exec', $namespace, function (string $command, mixed &$output = NULL, mixed &$result_code = NULL): string {
      $output = [];
      $result_code = 1;
      return '';
    });
  }

}
