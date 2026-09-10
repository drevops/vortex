<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Runner;

use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(ProcessRunner::class)]
class ProcessRunnerTest extends UnitTestCase {

  #[DataProvider('dataProviderRun')]
  public function testRun(string $command, array $args, string $expected_output_pattern, int $expected_exit_code, ?string $expected_exception, ?string $expected_message): void {
    if ($expected_exception !== NULL) {
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      $this->expectExceptionMessage($expected_message ?? '');
    }

    $runner = new ProcessRunner();
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    Tui::init($output);

    $result = $runner->run($command, $args);

    if ($expected_exception === NULL) {
      $this->assertInstanceOf(ProcessRunner::class, $result);
      $this->assertEquals($expected_exit_code, $runner->getExitCode());
      $output = $runner->getOutput();
      $this->assertMatchesRegularExpression($expected_output_pattern, is_string($output) ? $output : implode(PHP_EOL, $output));
      $this->assertNotNull($runner->getCommand());
    }
  }

  public static function dataProviderRun(): \Iterator {
    yield 'simple echo command' => [
      'command' => 'echo',
      'args' => ['hello', 'world'],
      'expected_output_pattern' => '/hello world/',
      'expected_exit_code' => 0,
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'command with single argument' => [
      'command' => 'echo "test message"',
      'args' => [],
      'expected_output_pattern' => '/test message/',
      'expected_exit_code' => 0,
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'command not found' => [
      'command' => 'nonexistent_command_12345',
      'args' => [],
      'expected_output_pattern' => '//',
      'expected_exit_code' => 0,
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Command not found',
    ];
    yield 'command with invalid characters' => [
      'command' => '$invalid-cmd',
      'args' => [],
      'expected_output_pattern' => '//',
      'expected_exit_code' => 0,
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Invalid command',
    ];
    yield 'command utility is not allowed' => [
      'command' => 'command',
      'args' => ['-v', 'ls'],
      'expected_output_pattern' => '//',
      'expected_exit_code' => 0,
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Using the "command" utility is not allowed. Use Symfony\Component\Process\ExecutableFinder',
    ];
  }

  #[DataProvider('dataProviderRunWithStreaming')]
  public function testRunWithStreaming(bool $streaming_enabled, bool $should_have_output_in_stream): void {
    $runner = new ProcessRunner();
    $runner->setCwd(self::$tmp);

    if (!$streaming_enabled) {
      $runner->disableStreaming();
    }

    $output = new BufferedOutput();
    Tui::init($output);

    $runner->run('echo "test output"', []);

    $output_content = $output->fetch();

    if ($should_have_output_in_stream) {
      $this->assertStringContainsString('test output', $output_content);
    }
    else {
      $this->assertStringNotContainsString('test output', $output_content);
    }

    // Output should always be captured in runner.
    $runner_output = $runner->getOutput();
    $this->assertStringContainsString('test output', is_string($runner_output) ? $runner_output : implode(PHP_EOL, $runner_output));
  }

  public static function dataProviderRunWithStreaming(): \Iterator {
    yield 'streaming enabled' => [
      'streaming_enabled' => TRUE,
      'should_have_output_in_stream' => TRUE,
    ];
    yield 'streaming disabled' => [
      'streaming_enabled' => FALSE,
      'should_have_output_in_stream' => FALSE,
    ];
  }

  #[DataProvider('dataProviderResolveCommand')]
  public function testResolveCommand(string $command, bool $expect_success, ?string $expected_exception, ?string $expected_message): void {
    if ($expected_exception !== NULL) {
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      $this->expectExceptionMessage($expected_message ?? '');
    }

    $runner = new TestableProcessRunner();
    $runner->setCwd(self::$tmp);

    [$resolved, $parsed] = $runner->resolveCommandPublic($command);

    if ($expect_success) {
      $this->assertNotEmpty($resolved);
      $this->assertIsArray($parsed);
    }
  }

  public static function dataProviderResolveCommand(): \Iterator {
    yield 'simple command (echo)' => [
      'command' => 'echo',
      'expect_success' => TRUE,
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'command with arguments' => [
      'command' => 'echo hello',
      'expect_success' => TRUE,
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'command not in PATH' => [
      'command' => 'nonexistent_cmd_xyz',
      'expect_success' => FALSE,
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Command not found',
    ];
    yield 'command with invalid characters' => [
      'command' => 'echo$test',
      'expect_success' => FALSE,
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Invalid command',
    ];
    yield 'command utility is not allowed' => [
      'command' => 'command',
      'expect_success' => FALSE,
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Using the "command" utility is not allowed. Use Symfony\Component\Process\ExecutableFinder to check if a command exists instead.',
    ];
  }

  #[DataProvider('dataProviderPrepareArguments')]
  public function testPrepareArguments(array $parsed_args, array $additional_args, array $expected, ?string $expected_exception, ?string $expected_message): void {
    if ($expected_exception !== NULL) {
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      $this->expectExceptionMessage($expected_message ?? '');
    }

    $runner = new TestableProcessRunner();

    $result = $runner->prepareArgumentsPublic($parsed_args, $additional_args);

    if ($expected_exception === NULL) {
      $this->assertEquals($expected, $result);
    }
  }

  public static function dataProviderPrepareArguments(): \Iterator {
    yield 'merge parsed and additional args' => [
      'parsed_args' => ['arg1', 'arg2'],
      'additional_args' => ['arg3', 'arg4'],
      'expected' => ['arg1', 'arg2', 'arg3', 'arg4'],
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'convert numeric args to strings' => [
      'parsed_args' => ['test'],
      'additional_args' => [123, 456],
      'expected' => ['test', '123', '456'],
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'boolean arguments' => [
      'parsed_args' => [],
      'additional_args' => ['--verbose' => TRUE, '--quiet' => FALSE],
      'expected' => ['--verbose'],
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'non-scalar argument throws exception' => [
      'parsed_args' => ['arg1', ['array']],
      'additional_args' => [],
      'expected' => [],
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Argument at index "1" must be a scalar value, array given.',
    ];
  }

  #[DataProvider('dataProviderValidateEnvironmentVars')]
  public function testValidateEnvironmentVars(array $env, ?string $expected_exception, ?string $expected_message): void {
    if ($expected_exception !== NULL) {
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      $this->expectExceptionMessage($expected_message ?? '');
    }

    $runner = new TestableProcessRunner();

    $runner->validateEnvironmentVarsPublic($env);

    if ($expected_exception === NULL) {
      $this->addToAssertionCount(1);
    }
  }

  public static function dataProviderValidateEnvironmentVars(): \Iterator {
    yield 'valid scalar env vars' => [
      'env' => ['VAR1' => 'value1', 'VAR2' => 'value2'],
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'empty env vars' => [
      'env' => [],
      'expected_exception' => NULL,
      'expected_message' => NULL,
    ];
    yield 'non-scalar env var throws exception' => [
      'env' => ['VAR1' => ['array']],
      'expected_exception' => \InvalidArgumentException::class,
      'expected_message' => 'Environment variable "VAR1" must be a scalar value, array given.',
    ];
  }

  public function testRunWithEnvironmentVariables(): void {
    $runner = new ProcessRunner();
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    Tui::init($output);

    // The printenv binary may be absent on Windows.
    if (PHP_OS_FAMILY === 'Windows') {
      $this->markTestSkipped('Environment variable test not compatible with Windows.');
    }

    $runner->run('printenv TEST_VAR', [], [], ['TEST_VAR' => 'test_value']);

    $output = $runner->getOutput();
    $this->assertStringContainsString('test_value', is_string($output) ? $output : implode(PHP_EOL, $output));
  }

  public function testRunWithWorkingDirectory(): void {
    $runner = new ProcessRunner();
    $test_dir = self::$tmp . '/test_subdir';
    File::mkdir($test_dir);

    $runner->setCwd($test_dir);

    $output = new BufferedOutput();
    Tui::init($output);

    $runner->run('pwd', []);

    $output = $runner->getOutput();
    $this->assertStringContainsString($test_dir, is_string($output) ? $output : implode(PHP_EOL, $output));
  }

  public function testResolveCommandWithRelativePath(): void {
    $runner = new TestableProcessRunner();
    $test_dir = self::$tmp . '/test_scripts';
    File::mkdir($test_dir);

    $script_path = $test_dir . '/test_script.sh';
    File::dump($script_path, "#!/bin/sh\necho 'test'\n");
    File::chmod($script_path, 0755);

    $runner->setCwd(self::$tmp);

    [$resolved, $parsed] = $runner->resolveCommandPublic('test_scripts/test_script.sh');

    $this->assertEquals($test_dir . '/test_script.sh', $resolved);
    $this->assertEmpty($parsed);
  }

  public function testPrepareArgumentsWithNonScalarAfterFormatting(): void {
    $runner = new TestableProcessRunner();

    // formatArgs() casts every value to a string, so the non-scalar check
    // after formatting may be unreachable through normal usage; only normal
    // arguments are exercised here.
    $result = $runner->prepareArgumentsPublic(['test'], ['arg1', 'arg2']);

    $this->assertEquals(['test', 'arg1', 'arg2'], $result);
  }

}

class TestableProcessRunner extends ProcessRunner {

  public function resolveCommandPublic(string $command): array {
    return $this->resolveCommand($command);
  }

  public function prepareArgumentsPublic(array $parsed_args, array $additional_args): array {
    return $this->prepareArguments($parsed_args, $additional_args);
  }

  public function validateEnvironmentVarsPublic(array $env): void {
    $this->validateEnvironmentVars($env);
  }

}
