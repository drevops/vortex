<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for shared Lagoon CLI helper functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\lagoon_cli_require')]
#[CoversFunction('DrevOps\VortexTooling\lagoon_config')]
#[CoversFunction('DrevOps\VortexTooling\lagoon_exec')]
#[CoversFunction('DrevOps\VortexTooling\lagoon_extract_backup')]
#[Group('helpers')]
#[RunTestsInSeparateProcesses]
class HelpersLagoonTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testCliRequirePresent(): void {
    $this->mockCommandExists();

    $this->assertSame('lagoon', \DrevOps\VortexTooling\lagoon_cli_require());
  }

  public function testCliRequireAbsent(): void {
    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec->expects($this->any())->willReturnCallback(function (string $command, mixed &$output = NULL, mixed &$result_code = NULL): string {
      $output = [];
      $result_code = 1;
      return '';
    });

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_cli_require();
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString("Command 'lagoon' is not available.", (string) $output);
    }
  }

  public function testConfigSuccess(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'",
      'output' => '',
      'result_code' => 0,
    ]);

    $output = $this->captureOutput(function (): void {
      \DrevOps\VortexTooling\lagoon_config('lagoon', 'amazeeio', 'https://api.lagoon.amazeeio.cloud/graphql', 'ssh.lagoon.amazeeio.cloud', '32222');
    });

    $this->assertSame('', $output);
  }

  public function testConfigFailure(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'",
      'output' => '',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_config('lagoon', 'amazeeio', 'https://api.lagoon.amazeeio.cloud/graphql', 'ssh.lagoon.amazeeio.cloud', '32222');
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Failed to add Lagoon instance configuration.', (string) $output);
    }
  }

  public function testExecWithSshKey(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' --force --skip-update-check --ssh-key '/home/user/.ssh/id_rsa' --lagoon 'amazeeio' --project 'myproject' list backups --environment 'main' --output-json --pretty 2>&1",
      'output' => '{"data":[]}',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', "list backups --environment 'main' --output-json --pretty", [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'ssh_key' => '/home/user/.ssh/id_rsa',
    ]);

    $this->assertSame('{"data":[]}', $result);
  }

  public function testExecWithoutSshKey(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' whoami 2>&1",
      'output' => 'authenticated',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', 'whoami', [
      'instance' => 'amazeeio',
      'project' => 'myproject',
    ]);

    $this->assertSame('authenticated', $result);
  }

  public function testExecSshKeyFalseOmitsIdentity(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' whoami 2>&1",
      'output' => 'authenticated',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', 'whoami', [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'ssh_key' => 'false',
    ]);

    $this->assertSame('authenticated', $result);
  }

  public function testExecSoftFailureCapturesExitCode(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' get backup --environment 'main' --backup-id 'abc' --output-json 2>&1",
      'output' => 'no download file found',
      'result_code' => 3,
    ]);

    $exit_code = 0;
    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', "get backup --environment 'main' --backup-id 'abc' --output-json", [
      'instance' => 'amazeeio',
      'project' => 'myproject',
    ], $exit_code);

    $this->assertSame(3, $exit_code);
    $this->assertSame('no download file found', $result);
  }

  public function testExecPreservesZeroOutput(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' whoami 2>&1",
      'output' => '0',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', 'whoami', [
      'instance' => 'amazeeio',
      'project' => 'myproject',
    ]);

    $this->assertSame('0', $result);
  }

  public function testExecHardFailure(): void {
    $this->mockPassthru([
      'cmd' => "'lagoon' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' list backups --environment 'main' 2>&1",
      'output' => 'boom',
      'result_code' => 2,
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_exec('lagoon', "list backups --environment 'main'", [
        'instance' => 'amazeeio',
        'project' => 'myproject',
      ]);
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Lagoon CLI command "list backups --environment \'main\'" failed with exit code 2', (string) $output);
    }
  }

  public function testExtractBackupGzip(): void {
    mkdir(self::$tmp . '/archive-src', 0755, TRUE);
    file_put_contents(self::$tmp . '/archive-src/dump.sql', 'SELECT 1;');

    $file = self::$tmp . '/db.sql';
    exec(sprintf('tar -czf %s -C %s dump.sql', escapeshellarg($file), escapeshellarg(self::$tmp . '/archive-src')));

    $output = $this->captureOutput(function () use ($file): void {
      \DrevOps\VortexTooling\lagoon_extract_backup($file);
    });

    $this->assertStringContainsString('Extracting the database backup.', $output);
    $this->assertSame('SELECT 1;', file_get_contents($file));
    $this->assertFileDoesNotExist($file . '.tar.gz');
  }

  public function testExtractBackupNonGzipLeavesFileUntouched(): void {
    $file = self::$tmp . '/db.sql';
    file_put_contents($file, 'plain sql content');

    \DrevOps\VortexTooling\lagoon_extract_backup($file);

    $this->assertSame('plain sql content', file_get_contents($file));
  }

}
