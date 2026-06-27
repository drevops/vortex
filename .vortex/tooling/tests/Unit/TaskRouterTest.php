<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for task router script.
 */
#[RunTestsInSeparateProcesses]
#[Group('task')]
class TaskRouterTest extends UnitTestCase {

  public function testFailureWhenOperationMissing(): void {
    $GLOBALS['argv'] = ['task'];

    $this->runScriptError('src/task', 'Missing task operation.');
  }

  #[DataProvider('dataProviderFailureWhenOperationUnsupported')]
  public function testFailureWhenOperationUnsupported(string $operation): void {
    $GLOBALS['argv'] = ['task', $operation];
    $this->envSet('VORTEX_PLATFORM', 'acquia');

    $this->runScriptError('src/task', "Unsupported task operation '" . $operation . "'.");
  }

  public static function dataProviderFailureWhenOperationUnsupported(): array {
    return [
      'invalid' => ['invalid'],
      'deploy' => ['deploy'],
      'copy' => ['copy'],
    ];
  }

  public function testFailureWhenPlatformMissing(): void {
    $GLOBALS['argv'] = ['task', 'copy-db'];
    $this->envUnset('VORTEX_PLATFORM');
    $this->envUnset('VORTEX_TASK_PLATFORM');

    $this->runScriptError('src/task', 'Missing hosting platform. Set VORTEX_PLATFORM or VORTEX_TASK_PLATFORM.');
  }

  public function testFailureWhenPlatformUnsupported(): void {
    $GLOBALS['argv'] = ['task', 'copy-db'];
    $this->envSet('VORTEX_PLATFORM', 'heroku');

    $this->runScriptError('src/task', "Unsupported hosting platform 'heroku'.");
  }

  public function testFailureWhenOperationNotSupportedOnPlatform(): void {
    $GLOBALS['argv'] = ['task', 'copy-db'];
    $this->envSet('VORTEX_PLATFORM', 'lagoon');

    $this->runScriptError('src/task', "Operation 'copy-db' is not supported on the 'lagoon' platform.");
  }

  public function testTaskPlatformOverridesPlatform(): void {
    $GLOBALS['argv'] = ['task', 'copy-db'];
    $this->envSet('VORTEX_PLATFORM', 'lagoon');
    $this->envSet('VORTEX_TASK_PLATFORM', 'acquia');

    $script_path = realpath(__DIR__ . '/../../src/task-copy-db-acquia');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'output' => 'Copied DB between Acquia environments',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/task');

    $this->assertStringContainsString('Copied DB between Acquia environments', $output);
  }

  #[DataProvider('dataProviderSuccessfulDispatch')]
  public function testSuccessfulDispatch(string $operation): void {
    $GLOBALS['argv'] = ['task', $operation];
    $this->envSet('VORTEX_PLATFORM', 'acquia');

    $script_path = realpath(__DIR__ . '/../../src/task-' . $operation . '-acquia');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'output' => 'Task completed',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/task');

    $this->assertStringContainsString('Task completed', $output);
  }

  public static function dataProviderSuccessfulDispatch(): array {
    return [
      'copy-db' => ['copy-db'],
      'copy-files' => ['copy-files'],
      'purge-cache' => ['purge-cache'],
    ];
  }

  public function testDispatchPassesThroughArguments(): void {
    $GLOBALS['argv'] = ['task', 'copy-db', 'extra'];
    $this->envSet('VORTEX_PLATFORM', 'acquia');

    $script_path = realpath(__DIR__ . '/../../src/task-copy-db-acquia');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . "\" 'extra'",
      'output' => 'Task completed',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/task');

    $this->assertStringContainsString('Task completed', $output);
  }

  public function testFailureWhenTaskScriptFails(): void {
    $GLOBALS['argv'] = ['task', 'copy-db'];
    $this->envSet('VORTEX_PLATFORM', 'acquia');

    $script_path = realpath(__DIR__ . '/../../src/task-copy-db-acquia');
    $this->mockPassthru([
      'cmd' => '"' . $script_path . '"',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/task', "Task 'copy-db' failed with exit code 1.");
  }

}
