<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\StringTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;
use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use DrevOps\VortexTooling\Tests\Traits\MockTrait;

/**
 * Abstract base class for unit tests with helper methods.
 */
abstract class UnitTestCase extends UpstreamUnitTestCase {

  use MockTrait;
  use EnvTrait;
  use StringTrait;

  protected function setUp(): void {
    parent::setUp();

    // Unset environment variables that may leak from the host and interfere
    // with tests via fallback chains in getenv_required()/getenv_default().
    self::envUnsetPrefix('S3_');
    self::envUnsetPrefix('LAGOON_');
    self::envUnsetPrefix('NEWRELIC_');
    self::envUnsetPrefix('DOCKER_');
    self::envUnsetPrefix('DATABASE_');
    self::envUnsetPrefix('DRUPAL_');
    self::envUnsetPrefix('COMPOSE_');
    self::envUnsetMultiple([
      'GITHUB_TOKEN',
    ]);
  }

  protected function tearDown(): void {
    self::envReset();

    $this->mockTearDown();

    parent::tearDown();
  }

  protected function runScript(string $script_path, ?int $early_exit_code = NULL, ?string $cwd = NULL): string {
    ob_start();

    // Change to the src directory by default so scripts resolve relative
    // paths consistently; $cwd overrides it for scripts that operate on the
    // current working directory (e.g. a sandboxed project fixture).
    $original_dir = getcwd();
    if ($original_dir === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException('Failed to get current working directory.');
      // @codeCoverageIgnoreEnd
    }

    $root = __DIR__ . '/../../src';
    if (!file_exists($root)) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException('Root directory not found: ' . $root);
      // @codeCoverageIgnoreEnd
    }

    chdir($cwd ?? $root);

    if (!is_null($early_exit_code)) {
      if ($early_exit_code > 0) {
        $this->mockQuit($early_exit_code);
        $this->expectException(QuitErrorException::class);
      }
      else {
        $this->mockQuit(0);
        $this->expectException(QuitSuccessException::class);
      }
    }

    $output = '';
    $cleared_buffer = FALSE;
    try {
      require __DIR__ . '/../../' . $script_path;
    }
    catch (QuitSuccessException $e) {
      $output = ob_get_clean() ?: '';
      $cleared_buffer = TRUE;
      throw new QuitSuccessException($e->getCode(), $output);
    }
    catch (QuitErrorException $e) {
      $output = ob_get_clean() ?: '';
      $cleared_buffer = TRUE;
      throw new QuitErrorException($e->getCode(), $output);
    }
    finally {
      if (!$cleared_buffer) {
        $output = ob_get_clean() ?: '';
      }
      chdir($original_dir);
    }

    return $output;
  }

  protected function runScriptEarlyPass(string $script_path, ?string $expected_output = NULL): void {
    try {
      $output = $this->runScript($script_path, 0);
    }
    catch (QuitSuccessException | QuitErrorException $e) {
      $output = $e->getOutput();
      if (!is_null($expected_output)) {
        $this->assertStringContainsString($expected_output, $output, 'Script output should contain expected output.');
      }

      throw $e;
    }
  }

  protected function runScriptError(string $script_path, ?string $expected_output = NULL): void {
    try {
      $output = $this->runScript($script_path, 1);
    }
    catch (QuitSuccessException | QuitErrorException $e) {
      $output = $e->getOutput();
      if (!is_null($expected_output)) {
        $this->assertStringContainsString($expected_output, $output, 'Script output should contain expected output.');
      }

      throw $e;
    }
  }

  /**
   * Capture the output of a callable.
   *
   * @param callable $callback
   *   The callable to execute.
   *
   * @return string
   *   The captured output.
   *
   * @throws \Throwable
   *   Rethrows any exception thrown by the callback.
   */
  protected function captureOutput(callable $callback): string {
    ob_start();
    try {
      $callback();
    }
    catch (\Throwable $e) {
      $output = ob_get_clean();
      throw $e;
    }
    return ob_get_clean() ?: '';
  }

  /**
   * Create a directory structure from a nested array definition.
   *
   * @param string $base_path
   *   Directory to create the structure under.
   * @param array $structure
   *   Nested array where a string value is a file with that content and an
   *   array value is a subdirectory.
   */
  protected function createDirectoryStructure(string $base_path, array $structure): void {
    if (!is_dir($base_path)) {
      mkdir($base_path, 0755, TRUE);
    }

    foreach ($structure as $name => $content) {
      $path = $base_path . '/' . $name;
      if (is_array($content)) {
        mkdir($path, 0755, TRUE);
        $this->createDirectoryStructure($path, $content);
      }
      else {
        file_put_contents($path, $content);
      }
    }
  }

  /**
   * Path to the isolated Lagoon CLI config file used in command assertions.
   *
   * Mirrors lagoon_config_file(): the file lives under VORTEX_LAGOONCLI_PATH
   * (set to self::$tmp in Lagoon tests) and is suffixed with the process ID.
   */
  protected function lagoonConfigFile(): string {
    return self::$tmp . '/lagoon-cli-' . getmypid() . '.yml';
  }

}
