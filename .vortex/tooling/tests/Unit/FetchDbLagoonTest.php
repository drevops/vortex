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
      'VORTEX_LAGOONCLI_PATH' => self::$tmp,
    ]);

    mkdir(self::$tmp . '/data', 0755, TRUE);
  }

  public function testMissingProject(): void {
    $this->envUnset('VORTEX_FETCH_DB_LAGOON_PROJECT');

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Missing required value for VORTEX_FETCH_DB_LAGOON_PROJECT, LAGOON_PROJECT');
  }

  public function testSetupSshFails(): void {
    $this->mockCommandExists();

    $this->mockPassthru(['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 1]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Failed to setup SSH.');
  }

  public function testReuseRecentDump(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([$this->dumpTask('461715', $this->recentTs())]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461715')), 'output' => $this->filesJson('https://storage.example.com/reuse.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/reuse.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('REUSED SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Looking for a recent database dump to reuse.', $output);
    $this->assertStringContainsString('Reused the database dump from task "461715".', $output);
    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'REUSED SQL DUMP');
  }

  public function testFreshWhenNoReusableDump(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      // No dump tasks exist yet.
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728},"result":"success"}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/fresh.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('FRESH SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('No reusable database dump; a fresh one will be created.', $output);
    $this->assertStringContainsString('Requested database dump task "461728".', $output);
    $this->assertStringContainsString('Database dump completed.', $output);
    $this->assertStringContainsString('Downloaded the database dump.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'FRESH SQL DUMP');
  }

  public function testFreshFlagSkipsReuse(): void {
    $this->envSet('VORTEX_FETCH_DB_FRESH', '1');
    $this->mockCommandExists();

    // No 'list tasks' call: the reuse lookup is skipped entirely.
    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/fresh.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('FRESH SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Fresh dump requested; skipping reuse of a previous dump.', $output);
    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

  public function testStaleDumpIsNotReused(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      // The only dump is older than the reuse window.
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([$this->dumpTask('111', $this->staleTs())]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/fresh.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('FRESH SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('No reusable database dump; a fresh one will be created.', $output);
    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

  public function testReuseWithoutFileFallsBackToFresh(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([$this->dumpTask('461715', $this->recentTs())]), 'result_code' => 0],
      // The recent dump has no downloadable artifact (purged).
      ['cmd' => $this->lagoonCmd($this->rawSub('461715')), 'output' => $this->noFilesJson(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/fresh.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('FRESH SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('No reusable database dump; a fresh one will be created.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'FRESH SQL DUMP');
  }

  public function testReuseDownloadFailureFallsBackToFresh(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([$this->dumpTask('461715', $this->recentTs())]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461715')), 'output' => $this->filesJson('https://storage.example.com/reuse.sql.gz'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequestMultiple([
      // The reused artifact is gone at download time.
      ['url' => 'https://storage.example.com/reuse.sql.gz', 'method' => 'GET', 'response' => ['status' => 404, 'ok' => FALSE, 'body' => '', 'error' => 'Not Found']],
      ['url' => 'https://storage.example.com/fresh.sql.gz', 'method' => 'GET', 'response' => ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('FRESH SQL DUMP')]],
    ]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('No reusable database dump; a fresh one will be created.', $output);
    $this->assertStringEqualsFile(self::$tmp . '/data/db.sql', 'FRESH SQL DUMP');
  }

  public function testFreshPollsUntilComplete(): void {
    $this->mockCommandExists();
    $this->mockSleep();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      // Still running on the first poll, complete on the second.
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'running'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/fresh.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('FRESH SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Waiting for the database dump to complete.', $output);
    $this->assertStringContainsString('Database dump completed.', $output);
  }

  public function testDumpTaskFailure(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'failed'), 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Database dump task "461728" failed.');
  }

  public function testPollTimeout(): void {
    $this->envSet('VORTEX_FETCH_DB_LAGOON_STATUS_RETRIES', '2');
    $this->mockCommandExists();
    $this->mockSleep();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'running'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'running'), 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Timed out waiting for the database dump task "461728".');
  }

  public function testNoDownloadableFile(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->noFilesJson(), 'result_code' => 0],
    ]);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'The database dump task "461728" produced no downloadable file.');
  }

  public function testDownloadFailure(): void {
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("run drush-sqldump --environment 'main' --output-json"), 'output' => '{"data":{"id":461728}}', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("get task-by-id --id '461728' --output-json"), 'output' => $this->taskStatusJson('461728', 'complete'), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461728')), 'output' => $this->filesJson('https://storage.example.com/fresh.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/fresh.sql.gz', ['method' => 'GET'], ['status' => 500, 'ok' => FALSE, 'body' => '', 'error' => 'Server error']);

    $this->runScriptError('src/vortex-fetch-db-lagoon', 'Failed to download the database dump from Lagoon.');
  }

  public function testInContainerSkipsSsh(): void {
    $this->envSet('VORTEX_FETCH_DB_SSH_FILE', 'false');
    $this->mockCommandExists();

    // No vortex-setup-ssh call and no --ssh-key flag in the CLI commands.
    $this->mockPassthruMultiple([
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmdNoSsh('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmdNoSsh("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([$this->dumpTask('461715', $this->recentTs())]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmdNoSsh($this->rawSub('461715')), 'output' => $this->filesJson('https://storage.example.com/reuse.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/reuse.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('REUSED SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Finished database dump download from Lagoon.', $output);
  }

  public function testDirectoryCreation(): void {
    $db_dir = self::$tmp . '/new-dir';
    $this->envSet('VORTEX_FETCH_DB_LAGOON_DB_DIR', $db_dir);
    $this->mockCommandExists();

    $this->mockPassthruMultiple([
      ['cmd' => self::$srcDir . '/vortex-setup-ssh', 'result_code' => 0],
      ['cmd' => $this->configCmd(), 'result_code' => 0],
      ['cmd' => $this->versionCmd(), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd('whoami'), 'output' => 'tester', 'result_code' => 0],
      ['cmd' => $this->lagoonCmd("list tasks --environment 'main' --output-json"), 'output' => $this->tasksJson([$this->dumpTask('461715', $this->recentTs())]), 'result_code' => 0],
      ['cmd' => $this->lagoonCmd($this->rawSub('461715')), 'output' => $this->filesJson('https://storage.example.com/reuse.sql.gz'), 'result_code' => 0],
    ]);

    $this->mockRequest('https://storage.example.com/reuse.sql.gz', ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => $this->gzipBody('REUSED SQL DUMP')]);

    $output = $this->runScript('src/vortex-fetch-db-lagoon');

    $this->assertStringContainsString('Creating directory for database dumps.', $output);
    $this->assertTrue(is_dir($db_dir));
  }

  /**
   * Builds a completed dump task entry.
   *
   * @return array<string, string>
   *   A task record as returned by 'lagoon list tasks'.
   */
  protected function dumpTask(string $id, string $created): array {
    return ['id' => $id, 'name' => 'Drush sql-dump', 'status' => 'complete', 'created' => $created];
  }

  /**
   * Encodes a 'list tasks' JSON response.
   *
   * @param array<int, array<string, string>> $tasks
   *   The task records to include.
   */
  protected function tasksJson(array $tasks): string {
    return json_encode(['data' => $tasks]) ?: '';
  }

  protected function taskStatusJson(string $id, string $status): string {
    return json_encode(['data' => [['id' => $id, 'status' => $status]]]) ?: '';
  }

  protected function filesJson(string $url): string {
    return json_encode(['taskById' => ['files' => [['download' => $url]]]]) ?: '';
  }

  protected function noFilesJson(): string {
    return json_encode(['taskById' => ['files' => []]]) ?: '';
  }

  protected function gzipBody(string $content): string {
    return gzencode($content) ?: '';
  }

  protected function recentTs(): string {
    return gmdate('Y-m-d H:i:s', time() - 3600);
  }

  protected function staleTs(): string {
    return gmdate('Y-m-d H:i:s', time() - 200000);
  }

  protected function configFile(): string {
    return self::$tmp . '/lagoon-cli.yml';
  }

  protected function configCmd(): string {
    return sprintf("'lagoon' --config-file '%s' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'", $this->configFile());
  }

  protected function versionCmd(): string {
    return sprintf("'lagoon' --config-file '%s' --version 2>&1", $this->configFile());
  }

  protected function lagoonCmd(string $subcommand): string {
    return sprintf("'lagoon' --config-file '%s' --force --skip-update-check --ssh-key '%s' --lagoon 'amazeeio' --project 'myproject' %s 2>&1", $this->configFile(), self::$sshFile, $subcommand);
  }

  protected function lagoonCmdNoSsh(string $subcommand): string {
    return sprintf("'lagoon' --config-file '%s' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' %s 2>&1", $this->configFile(), $subcommand);
  }

  protected function rawSub(string $id): string {
    return sprintf("raw --raw 'query{taskById(id:%s){files{download}}}'", $id);
  }

}
