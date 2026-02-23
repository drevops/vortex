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

    self::envUnsetMultiple([
      'GITHUB_TOKEN',
      'NEWRELIC_USER_KEY',
    ]);
  }

  protected function tearDown(): void {
    self::envReset();

    $this->mockTearDown();

    parent::tearDown();
  }

  protected function runScript(string $script_path, ?int $early_exit_code = NULL): string {
    ob_start();

    // Change to src directory so __DIR__ works correctly in the script.
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

    chdir($root);

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

}
