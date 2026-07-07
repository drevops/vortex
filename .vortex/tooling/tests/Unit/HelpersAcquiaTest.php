<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for shared Acquia CLI helper functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\acli_resolve')]
#[CoversFunction('DrevOps\VortexTooling\acli_home')]
#[CoversFunction('DrevOps\VortexTooling\acli_exec')]
#[Group('helpers')]
#[RunTestsInSeparateProcesses]
class HelpersAcquiaTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testResolveUsesPath(): void {
    $this->mockCommandExists();

    $result = '';
    $output = $this->captureOutput(function () use (&$result): void {
      $result = \DrevOps\VortexTooling\acli_resolve();
    });

    $this->assertSame('acli', $result);
    $this->assertStringContainsString('Using the Acquia CLI found on PATH.', $output);
  }

  public function testResolveReusesCached(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('acli');
    $dir = self::$tmp . '/cli';
    mkdir($dir, 0755, TRUE);
    $bin = $dir . '/acli';
    file_put_contents($bin, "#!/bin/sh\n");
    chmod($bin, 0755);
    $this->envSet('VORTEX_ACLI_PATH', $dir);

    $result = '';
    $output = $this->captureOutput(function () use (&$result): void {
      $result = \DrevOps\VortexTooling\acli_resolve();
    });

    $this->assertSame($bin, $result);
    $this->assertStringContainsString('Reusing the Acquia CLI', $output);
  }

  public function testResolveDownloads(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('acli');
    $dir = self::$tmp . '/cli';
    $this->envSetMultiple([
      'VORTEX_ACLI_PATH' => $dir,
      'VORTEX_ACLI_VERSION' => '2.61.3',
    ]);

    $url = 'https://github.com/acquia/cli/releases/download/2.61.3/acli.phar';
    $this->mockRequest($url, ['method' => 'GET'], ['status' => 200, 'ok' => TRUE, 'body' => '']);

    $result = '';
    $output = $this->captureOutput(function () use (&$result): void {
      $result = \DrevOps\VortexTooling\acli_resolve();
    });

    $this->assertSame($dir . '/acli', $result);
    $this->assertStringContainsString('Downloading the Acquia CLI', $output);
    $this->assertFileExists($dir . '/acli');
  }

  public function testResolveDownloadFails(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('acli');
    $this->envSetMultiple([
      'VORTEX_ACLI_PATH' => self::$tmp . '/cli',
      'VORTEX_ACLI_VERSION' => '2.61.3',
    ]);

    $url = 'https://github.com/acquia/cli/releases/download/2.61.3/acli.phar';
    $this->mockRequest($url, ['method' => 'GET'], ['status' => 404, 'ok' => FALSE, 'body' => '', 'error' => 'Not Found']);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\acli_resolve();
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Failed to download the Acquia CLI', (string) $output);
    }
  }

  public function testHomeCreatesIsolatedDir(): void {
    $this->envSet('VORTEX_ACLI_PATH', self::$tmp);

    $result = \DrevOps\VortexTooling\acli_home();

    $this->assertSame(self::$tmp . '/acli-home-' . getmypid(), $result);
    $this->assertDirectoryExists($result);
  }

  public function testHomeReusesExistingDir(): void {
    $this->envSet('VORTEX_ACLI_PATH', self::$tmp);
    $home = self::$tmp . '/acli-home-' . getmypid();
    mkdir($home, 0755, TRUE);

    $result = \DrevOps\VortexTooling\acli_home();

    $this->assertSame($home, $result);
    $this->assertDirectoryExists($result);
  }

  public function testExecSuccess(): void {
    $ctx = ['home' => self::$tmp . '/home', 'key' => 'k', 'secret' => 's'];
    $this->mockPassthru([
      'cmd' => $this->acliExecCmd('api:applications:list'),
      'output' => '{"_embedded":{"items":[]}}',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\acli_exec('acli', 'api:applications:list', $ctx);

    $this->assertSame('{"_embedded":{"items":[]}}', $result);
  }

  public function testExecSoftFailureCapturesExitCode(): void {
    $ctx = ['home' => self::$tmp . '/home', 'key' => 'k', 'secret' => 's'];
    $this->mockPassthru([
      'cmd' => $this->acliExecCmd('api:applications:list'),
      'output' => 'not authenticated',
      'result_code' => 3,
    ]);

    $exit_code = 0;
    $result = \DrevOps\VortexTooling\acli_exec('acli', 'api:applications:list', $ctx, $exit_code);

    $this->assertSame(3, $exit_code);
    $this->assertSame('not authenticated', $result);
  }

  public function testExecPreservesZeroOutput(): void {
    $ctx = ['home' => self::$tmp . '/home', 'key' => 'k', 'secret' => 's'];
    $this->mockPassthru([
      'cmd' => $this->acliExecCmd('--version'),
      'output' => '0',
      'result_code' => 0,
    ]);

    $result = \DrevOps\VortexTooling\acli_exec('acli', '--version', $ctx);

    $this->assertSame('0', $result);
  }

  public function testExecHardFailure(): void {
    $ctx = ['home' => self::$tmp . '/home', 'key' => 'k', 'secret' => 's'];
    $this->mockPassthru([
      'cmd' => $this->acliExecCmd('api:applications:list'),
      'output' => 'boom',
      'result_code' => 2,
    ]);

    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\acli_exec('acli', 'api:applications:list', $ctx);
      $this->fail('Expected QuitErrorException to be thrown.');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertStringContainsString('Acquia CLI command "api:applications:list" failed with exit code 2', (string) $output);
    }
  }

  /**
   * Builds the expected shell command produced by acli_exec().
   *
   * @param string $subcommand
   *   The subcommand with its command-specific arguments.
   */
  protected function acliExecCmd(string $subcommand): string {
    return sprintf('%s %s --no-interaction 2>&1', escapeshellarg('acli'), $subcommand);
  }

}
