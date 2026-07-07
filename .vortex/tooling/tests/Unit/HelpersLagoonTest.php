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
#[CoversFunction('DrevOps\VortexTooling\lagoon_cli_resolve')]
#[CoversFunction('DrevOps\VortexTooling\lagoon_cli_verify_checksum')]
#[CoversFunction('DrevOps\VortexTooling\lagoon_config')]
#[CoversFunction('DrevOps\VortexTooling\lagoon_exec')]
#[Group('helpers')]
#[RunTestsInSeparateProcesses]
class HelpersLagoonTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testResolveUsesPath(): void {
    $this->mockCommandExists();

    $result = '';
    $output = $this->captureOutput(function () use (&$result): void {
      $result = \DrevOps\VortexTooling\lagoon_cli_resolve();
    });

    $this->assertSame('lagoon', $result);
    $this->assertStringContainsString('Using the Lagoon CLI found on PATH.', $output);
  }

  public function testResolveReusesCached(): void {
    $this->mockCommandExists();
    $dir = self::$tmp . '/cli';
    mkdir($dir, 0755, TRUE);
    $bin = $dir . '/lagoon';
    file_put_contents($bin, "#!/bin/sh\n");
    chmod($bin, 0755);
    $this->mockCommandMissing('lagoon');
    $this->envSet('VORTEX_LAGOONCLI_PATH', $dir);

    $result = '';
    $output = $this->captureOutput(function () use (&$result): void {
      $result = \DrevOps\VortexTooling\lagoon_cli_resolve();
    });

    $this->assertSame($bin, $result);
    $this->assertStringContainsString('Reusing the Lagoon CLI', $output);
  }

  public function testResolveDownloads(): void {
    $this->mockCommandExists();
    $dir = self::$tmp . '/cli';
    $this->mockCommandMissing('lagoon');
    $this->envSetMultiple([
      'VORTEX_LAGOONCLI_PATH' => $dir,
      'VORTEX_LAGOONCLI_VERSION' => 'v0.32.0',
    ]);

    [$base, $asset] = $this->releaseUrl();
    // A mocked download saves an empty file, so verify against the empty hash.
    $sha = hash('sha256', '');

    $this->mockRequestMultiple([
      ['url' => $base . '/' . $asset, 'method' => 'GET', 'response' => ['status' => 200, 'ok' => TRUE, 'body' => '']],
      ['url' => $base . '/checksums.txt', 'method' => 'GET', 'response' => ['status' => 200, 'ok' => TRUE, 'body' => $sha . '  ' . $asset]],
    ]);

    $result = '';
    $output = $this->captureOutput(function () use (&$result): void {
      $result = \DrevOps\VortexTooling\lagoon_cli_resolve();
    });

    $this->assertSame($dir . '/lagoon', $result);
    $this->assertStringContainsString('Downloading the Lagoon CLI', $output);
    $this->assertFileExists($dir . '/lagoon');
  }

  public function testResolveDownloadFails(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('lagoon');
    $this->envSet('VORTEX_LAGOONCLI_PATH', self::$tmp . '/cli');

    [$base, $asset] = $this->releaseUrl();
    $this->mockRequest($base . '/' . $asset, ['method' => 'GET'], ['status' => 404, 'ok' => FALSE, 'body' => '', 'error' => 'Not Found']);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_cli_resolve();
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Failed to download the Lagoon CLI', (string) $output);
    }
  }

  public function testResolveChecksumMismatch(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('lagoon');
    $this->envSet('VORTEX_LAGOONCLI_PATH', self::$tmp . '/cli');

    [$base, $asset] = $this->releaseUrl();
    $this->mockRequestMultiple([
      ['url' => $base . '/' . $asset, 'method' => 'GET', 'response' => ['status' => 200, 'ok' => TRUE, 'body' => '']],
      ['url' => $base . '/checksums.txt', 'method' => 'GET', 'response' => ['status' => 200, 'ok' => TRUE, 'body' => 'deadbeef  ' . $asset]],
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_cli_resolve();
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Lagoon CLI checksum verification failed', (string) $output);
    }
  }

  public function testResolveChecksumDownloadFails(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('lagoon');
    $this->envSet('VORTEX_LAGOONCLI_PATH', self::$tmp . '/cli');

    [$base, $asset] = $this->releaseUrl();
    $this->mockRequestMultiple([
      ['url' => $base . '/' . $asset, 'method' => 'GET', 'response' => ['status' => 200, 'ok' => TRUE, 'body' => '']],
      ['url' => $base . '/checksums.txt', 'method' => 'GET', 'response' => ['status' => 500, 'ok' => FALSE, 'body' => '', 'error' => 'Server error']],
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_cli_resolve();
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Failed to download the Lagoon CLI checksums', (string) $output);
    }
  }

  public function testConfigSuccess(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'", $config_file),
      'output' => '',
      'result_code' => 0,
    ]);

    $output = $this->captureOutput(function () use ($config_file): void {
      \DrevOps\VortexTooling\lagoon_config('lagoon', $config_file, 'amazeeio', 'https://api.lagoon.amazeeio.cloud/graphql', 'ssh.lagoon.amazeeio.cloud', '32222');
    });

    $this->assertSame('', $output);
    // The isolated config file is seeded with a valid, empty instance map.
    $this->assertStringEqualsFile($config_file, "lagoons: {}\n");
  }

  public function testConfigFailure(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'", $config_file),
      'output' => '',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_config('lagoon', $config_file, 'amazeeio', 'https://api.lagoon.amazeeio.cloud/graphql', 'ssh.lagoon.amazeeio.cloud', '32222');
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
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' --force --skip-update-check --ssh-key '/home/user/.ssh/id_rsa' --lagoon 'amazeeio' --project 'myproject' list backups --environment 'main' --output-json --pretty 2>&1", $config_file),
      'output' => '{"data":[]}',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', "list backups --environment 'main' --output-json --pretty", [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'config_file' => $config_file,
      'ssh_key' => '/home/user/.ssh/id_rsa',
    ]);

    $this->assertSame('{"data":[]}', $result);
  }

  public function testExecWithoutSshKey(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' whoami 2>&1", $config_file),
      'output' => 'authenticated',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', 'whoami', [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'config_file' => $config_file,
    ]);

    $this->assertSame('authenticated', $result);
  }

  public function testExecSshKeyFalseOmitsIdentity(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' whoami 2>&1", $config_file),
      'output' => 'authenticated',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', 'whoami', [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'config_file' => $config_file,
      'ssh_key' => 'false',
    ]);

    $this->assertSame('authenticated', $result);
  }

  public function testExecSoftFailureCapturesExitCode(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' get backup --environment 'main' --backup-id 'abc' --output-json 2>&1", $config_file),
      'output' => 'no download file found',
      'result_code' => 3,
    ]);

    $exit_code = 0;
    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', "get backup --environment 'main' --backup-id 'abc' --output-json", [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'config_file' => $config_file,
    ], $exit_code);

    $this->assertSame(3, $exit_code);
    $this->assertSame('no download file found', $result);
  }

  public function testExecPreservesZeroOutput(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' whoami 2>&1", $config_file),
      'output' => '0',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\lagoon_exec('lagoon', 'whoami', [
      'instance' => 'amazeeio',
      'project' => 'myproject',
      'config_file' => $config_file,
    ]);

    $this->assertSame('0', $result);
  }

  public function testExecHardFailure(): void {
    $config_file = $this->lagoonConfigFile();
    $this->mockPassthru([
      'cmd' => sprintf("'lagoon' --config-file '%s' --force --skip-update-check --lagoon 'amazeeio' --project 'myproject' list backups --environment 'main' 2>&1", $config_file),
      'output' => 'boom',
      'result_code' => 2,
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\lagoon_exec('lagoon', "list backups --environment 'main'", [
        'instance' => 'amazeeio',
        'project' => 'myproject',
        'config_file' => $config_file,
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

  /**
   * Builds the release base URL and asset name for the current platform.
   *
   * @return array{0: string, 1: string}
   *   The base URL and the asset file name.
   */
  protected function releaseUrl(): array {
    $platform = strtolower(php_uname('s'));
    $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));

    return [
      'https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0',
      sprintf('lagoon-cli-v0.32.0-%s-%s', $platform, $arch),
    ];
  }

}
