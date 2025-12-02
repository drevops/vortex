<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Runner;

use DrevOps\VortexInstaller\Utils\Tui;
use DrevOps\VortexInstaller\Logger\FileLogger;
use DrevOps\VortexInstaller\Logger\FileLoggerInterface;
use DrevOps\VortexInstaller\Runner\AbstractRunner;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for AbstractRunner class.
 */
#[CoversClass(AbstractRunner::class)]
class AbstractRunnerTest extends UnitTestCase {

  /**
   * Test getLogger creates FileLogger instance lazily.
   */
  public function testGetLoggerCreatesInstanceLazily(): void {
    $runner = new ConcreteRunner();

    $logger1 = $runner->getLogger();
    $this->assertInstanceOf(FileLogger::class, $logger1);

    $logger2 = $runner->getLogger();
    $this->assertSame($logger1, $logger2, 'getLogger() should return the same instance on subsequent calls');
  }

  /**
   * Test getCwd returns current directory by default.
   */
  public function testGetCwdReturnsCurrentDirectory(): void {
    $runner = new ConcreteRunner();

    $cwd = $runner->getCwd();
    $this->assertEquals(getcwd(), $cwd);
  }

  /**
   * Test setCwd sets custom directory.
   */
  public function testSetCwdSetsCustomDirectory(): void {
    $runner = new ConcreteRunner();

    $result = $runner->setCwd('/custom/path');
    $this->assertEquals('/custom/path', $runner->getCwd());
    $this->assertInstanceOf(AbstractRunner::class, $result, 'setCwd() should return self for method chaining');
  }

  /**
   * Test setCwd updates logger directory.
   */
  public function testSetCwdUpdatesLoggerDirectory(): void {
    $runner = new ConcreteRunner();
    $logger = $runner->getLogger();

    $runner->setCwd(self::$tmp);

    $this->assertEquals(self::$tmp, $logger->getDir());
  }

  /**
   * Test enableLog calls logger's enable.
   */
  public function testEnableLog(): void {
    $runner = new ConcreteRunner();
    $logger = $runner->getLogger();

    $logger->disable();
    $this->assertFalse($logger->isEnabled());

    $result = $runner->enableLog();
    $this->assertTrue($logger->isEnabled());
    $this->assertInstanceOf(AbstractRunner::class, $result, 'enableLog() should return self for method chaining');
  }

  /**
   * Test disableLog calls logger's disable.
   */
  public function testDisableLog(): void {
    $runner = new ConcreteRunner();
    $logger = $runner->getLogger();

    $this->assertTrue($logger->isEnabled());

    $result = $runner->disableLog();
    $this->assertFalse($logger->isEnabled());
    $this->assertInstanceOf(AbstractRunner::class, $result, 'disableLog() should return self for method chaining');
  }

  /**
   * Test enableStreaming sets internal flag.
   */
  public function testEnableStreaming(): void {
    $runner = new ConcreteRunner();

    // Streaming is enabled by default.
    $this->assertTrue($runner->shouldStream());

    $runner->disableStreaming();
    $this->assertFalse($runner->shouldStream());

    $result = $runner->enableStreaming();
    $this->assertTrue($runner->shouldStream());
    $this->assertInstanceOf(AbstractRunner::class, $result, 'enableStreaming() should return self for method chaining');
  }

  /**
   * Test disableStreaming sets internal flag.
   */
  public function testDisableStreaming(): void {
    $runner = new ConcreteRunner();

    $this->assertTrue($runner->shouldStream());

    $result = $runner->disableStreaming();
    $this->assertFalse($runner->shouldStream());
    $this->assertInstanceOf(AbstractRunner::class, $result, 'disableStreaming() should return self for method chaining');
  }

  /**
   * Test getCommand returns NULL initially.
   */
  public function testGetCommandInitiallyNull(): void {
    $runner = new ConcreteRunner();

    $this->assertNull($runner->getCommand());
  }

  /**
   * Test getExitCode returns 0 initially.
   */
  public function testGetExitCodeInitiallyZero(): void {
    $runner = new ConcreteRunner();

    $this->assertEquals(0, $runner->getExitCode());
  }

  /**
   * Test getOutput returns empty string initially.
   */
  public function testGetOutputInitiallyEmpty(): void {
    $runner = new ConcreteRunner();

    $this->assertEquals('', $runner->getOutput());
  }

  /**
   * Test parseCommand with various formats.
   */
  #[DataProvider('dataProviderParseCommand')]
  public function testParseCommand(string $command, array $expected, ?string $expected_exception, ?string $expected_message): void {
    if ($expected_exception !== NULL) {
      /** @var class-string<\Throwable> $expected_exception */
      $this->expectException($expected_exception);
      $this->expectExceptionMessage($expected_message ?? '');
    }

    $runner = new ConcreteRunner();
    $result = $runner->parseCommandPublic($command);

    if ($expected_exception === NULL) {
      $this->assertEquals($expected, $result);
    }
  }

  /**
   * Data provider for parseCommand.
   */
  public static function dataProviderParseCommand(): array {
    return [
      'simple command' => [
        'command' => 'echo',
        'expected' => ['echo'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with arguments' => [
        'command' => 'echo hello world',
        'expected' => ['echo', 'hello', 'world'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with single-quoted argument' => [
        'command' => "echo 'hello world'",
        'expected' => ['echo', 'hello world'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with double-quoted argument' => [
        'command' => 'echo "hello world"',
        'expected' => ['echo', 'hello world'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with escaped character' => [
        'command' => 'echo hello\\ world',
        'expected' => ['echo', 'hello world'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with escaped quote inside single quotes' => [
        'command' => "echo 'It\\'s working'",
        'expected' => ['echo', "It's working"],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with mixed quotes' => [
        'command' => 'echo "hello" \'world\'',
        'expected' => ['echo', 'hello', 'world'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with end-of-options marker' => [
        'command' => 'echo -- --not-an-option',
        'expected' => ['echo', '--', '--not-an-option'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with single short option' => [
        'command' => 'ls -l',
        'expected' => ['ls', '-l'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with multiple short options' => [
        'command' => 'ls -la',
        'expected' => ['ls', '-la'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with separate short options' => [
        'command' => 'ls -l -a -h',
        'expected' => ['ls', '-l', '-a', '-h'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with long option' => [
        'command' => 'ls --all',
        'expected' => ['ls', '--all'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with long option with equals value' => [
        'command' => 'composer require --dev=phpunit',
        'expected' => ['composer', 'require', '--dev=phpunit'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with long option with space-separated value' => [
        'command' => 'git commit -m "commit message"',
        'expected' => ['git', 'commit', '-m', 'commit message'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with option value with equals' => [
        'command' => 'command --option=value',
        'expected' => ['command', '--option=value'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with option value with spaces' => [
        'command' => 'command --option="value with spaces"',
        'expected' => ['command', '--option=value with spaces'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with mixed options and arguments' => [
        'command' => 'ls -la /path/to/dir',
        'expected' => ['ls', '-la', '/path/to/dir'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with options and quoted arguments' => [
        'command' => 'grep -r "search term" /path/to/dir',
        'expected' => ['grep', '-r', 'search term', '/path/to/dir'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'complex command with multiple options and arguments' => [
        'command' => 'docker run -it --rm --name=mycontainer -v /host:/container ubuntu:latest bash',
        'expected' => ['docker', 'run', '-it', '--rm', '--name=mycontainer', '-v', '/host:/container', 'ubuntu:latest', 'bash'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with option containing special characters' => [
        'command' => 'curl -H "Authorization: Bearer token123"',
        'expected' => ['curl', '-H', 'Authorization: Bearer token123'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with multiple long options with values' => [
        'command' => 'command --option1=value1 --option2=value2 --flag',
        'expected' => ['command', '--option1=value1', '--option2=value2', '--flag'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with mixed short and long options' => [
        'command' => 'command -a -b --long-option --another=value arg1 arg2',
        'expected' => ['command', '-a', '-b', '--long-option', '--another=value', 'arg1', 'arg2'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with options before and after arguments' => [
        'command' => 'find /path -name "*.txt" -type f',
        'expected' => ['find', '/path', '-name', '*.txt', '-type', 'f'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with option value containing equals sign' => [
        'command' => 'command --url="http://example.com?param=value"',
        'expected' => ['command', '--url=http://example.com?param=value'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with negative number argument' => [
        'command' => 'command -n -42',
        'expected' => ['command', '-n', '-42'],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'command with option and empty string value' => [
        'command' => 'command --option=""',
        'expected' => ['command', '--option='],
        'expected_exception' => NULL,
        'expected_message' => NULL,
      ],
      'empty command' => [
        'command' => '',
        'expected' => [],
        'expected_exception' => \InvalidArgumentException::class,
        'expected_message' => 'Command cannot be empty.',
      ],
      'whitespace only command' => [
        'command' => '   ',
        'expected' => [],
        'expected_exception' => \InvalidArgumentException::class,
        'expected_message' => 'Command cannot be empty.',
      ],
      'unclosed single quote' => [
        'command' => "echo 'unclosed",
        'expected' => [],
        'expected_exception' => \InvalidArgumentException::class,
        'expected_message' => 'Unclosed quote in command string.',
      ],
      'unclosed double quote' => [
        'command' => 'echo "unclosed',
        'expected' => [],
        'expected_exception' => \InvalidArgumentException::class,
        'expected_message' => 'Unclosed quote in command string.',
      ],
      'trailing escape' => [
        'command' => 'echo trailing\\',
        'expected' => [],
        'expected_exception' => \InvalidArgumentException::class,
        'expected_message' => 'Trailing escape character in command string.',
      ],
    ];
  }

  /**
   * Test reset method.
   */
  public function testReset(): void {
    $runner = new ConcreteRunner();

    $runner->setCommand('test-command');
    $runner->setOutput('test output');
    $runner->setExitCode(1);

    $this->assertEquals('test-command', $runner->getCommand());
    $this->assertEquals('test output', $runner->getOutput());
    $this->assertEquals(1, $runner->getExitCode());

    $runner->resetPublic();

    $this->assertNull($runner->getCommand());
    $this->assertEquals('', $runner->getOutput());
    $this->assertEquals(0, $runner->getExitCode());
  }

  /**
   * Test initLogger sets correct directory and opens log.
   */
  public function testInitLogger(): void {
    $runner = new ConcreteRunner();
    $runner->setCwd(self::$tmp);

    $logger = $runner->initLoggerPublic('test-command', ['arg1', 'arg2']);

    $this->assertInstanceOf(FileLogger::class, $logger);
    $this->assertEquals(self::$tmp, $logger->getDir());

    $path = $logger->getPath();
    $this->assertNotNull($path);
    $this->assertStringContainsString('test-command', $path);
    $this->assertStringContainsString('arg1', $path);
    $this->assertStringContainsString('arg2', $path);

    $logger->close();
  }

  /**
   * Test resolveOutput with NULL uses default.
   */
  public function testResolveOutputWithNull(): void {
    $runner = new ConcreteRunner();

    // Initialize Tui with a mock output first.
    $mock_output = $this->createMock(OutputInterface::class);
    Tui::init($mock_output);

    $output = $runner->resolveOutputPublic(NULL);

    $this->assertInstanceOf(OutputInterface::class, $output);
    $this->assertSame($mock_output, $output);
  }

  /**
   * Test resolveOutput with provided output.
   */
  public function testResolveOutputWithProvided(): void {
    $runner = new ConcreteRunner();
    $mock_output = $this->createMock(OutputInterface::class);

    $output = $runner->resolveOutputPublic($mock_output);

    $this->assertSame($mock_output, $output);
  }

  /**
   * Test getOutput with as_array parameter.
   */
  #[DataProvider('dataProviderGetOutputVariations')]
  public function testGetOutputVariations(string $output, bool $as_array, ?int $lines, string | array $expected): void {
    $runner = new ConcreteRunner();
    $runner->setOutput($output);

    $result = $runner->getOutput($as_array, $lines);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for getOutput variations.
   */
  public static function dataProviderGetOutputVariations(): array {
    return [
      'string output, as_array=false, no limit' => [
        'output' => "Line 1\nLine 2\nLine 3",
        'as_array' => FALSE,
        'lines' => NULL,
        'expected' => "Line 1\nLine 2\nLine 3",
      ],
      'string output, as_array=true, no limit' => [
        'output' => "Line 1\nLine 2\nLine 3",
        'as_array' => TRUE,
        'lines' => NULL,
        'expected' => ['Line 1', 'Line 2', 'Line 3'],
      ],
      'string output, as_array=false, limit=2' => [
        'output' => "Line 1\nLine 2\nLine 3",
        'as_array' => FALSE,
        'lines' => 2,
        'expected' => "Line 1\nLine 2",
      ],
      'string output, as_array=true, limit=2' => [
        'output' => "Line 1\nLine 2\nLine 3",
        'as_array' => TRUE,
        'lines' => 2,
        'expected' => ['Line 1', 'Line 2'],
      ],
      'empty output, as_array=false' => [
        'output' => '',
        'as_array' => FALSE,
        'lines' => NULL,
        'expected' => '',
      ],
      'empty output, as_array=true' => [
        'output' => '',
        'as_array' => TRUE,
        'lines' => NULL,
        'expected' => [''],
      ],
    ];
  }

  /**
   * Test buildCommandString with various arguments.
   */
  #[DataProvider('dataProviderBuildCommandString')]
  public function testBuildCommandString(string $command, array $args, array $opts, string $expected): void {
    $runner = new ConcreteRunner();

    $result = $runner->buildCommandStringPublic($command, $args, $opts);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for buildCommandString.
   */
  public static function dataProviderBuildCommandString(): array {
    return [
      'command only' => [
        'command' => 'echo',
        'args' => [],
        'opts' => [],
        'expected' => 'echo',
      ],
      'command with positional args' => [
        'command' => 'echo',
        'args' => ['hello', 'world'],
        'opts' => [],
        'expected' => 'echo hello world',
      ],
      'command with named options' => [
        'command' => 'echo',
        'args' => [],
        'opts' => ['--verbose' => TRUE, '--format' => 'json'],
        'expected' => 'echo --verbose --format=json',
      ],
      'command with mixed args and options' => [
        'command' => 'echo',
        'args' => ['hello'],
        'opts' => ['--verbose' => TRUE],
        'expected' => 'echo hello --verbose',
      ],
      'argument with spaces requires quoting' => [
        'command' => 'echo',
        'args' => ['hello world'],
        'opts' => [],
        'expected' => "echo 'hello world'",
      ],
      'empty string argument' => [
        'command' => 'echo',
        'args' => [''],
        'opts' => [],
        'expected' => "echo ''",
      ],
    ];
  }

  /**
   * Test quoteArgument method.
   */
  #[DataProvider('dataProviderQuoteArgument')]
  public function testQuoteArgument(string $argument, string $expected): void {
    $runner = new ConcreteRunner();

    $result = $runner->quoteArgumentPublic($argument);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for quoteArgument.
   */
  public static function dataProviderQuoteArgument(): array {
    return [
      'simple string (no quoting)' => [
        'argument' => 'hello',
        'expected' => 'hello',
      ],
      'string with spaces' => [
        'argument' => 'hello world',
        'expected' => "'hello world'",
      ],
      'string with single quote' => [
        'argument' => "It's working",
        'expected' => "'It'\\''s working'",
      ],
      'string with double quote' => [
        'argument' => 'Say "hello"',
        'expected' => "'Say \"hello\"'",
      ],
      'string with shell special chars' => [
        'argument' => 'test$variable',
        'expected' => "'test\$variable'",
      ],
      'empty string' => [
        'argument' => '',
        'expected' => "''",
      ],
      'string with backslash' => [
        'argument' => 'path\\to\\file',
        'expected' => "'path\\to\\file'",
      ],
    ];
  }

  /**
   * Test formatArgs method.
   */
  #[DataProvider('dataProviderFormatArgs')]
  public function testFormatArgs(array $args, array $expected): void {
    $runner = new ConcreteRunner();

    $result = $runner->formatArgsPublic($args);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for formatArgs.
   */
  public static function dataProviderFormatArgs(): array {
    return [
      'positional args' => [
        'args' => ['arg1', 'arg2'],
        'expected' => ['arg1', 'arg2'],
      ],
      'named args with string values' => [
        'args' => ['--option' => 'value', '--flag' => 'enabled'],
        'expected' => ['--option=value', '--flag=enabled'],
      ],
      'named args with bool TRUE' => [
        'args' => ['--verbose' => TRUE],
        'expected' => ['--verbose'],
      ],
      'named args with bool FALSE (excluded)' => [
        'args' => ['--verbose' => FALSE],
        'expected' => [],
      ],
      'positional args with bool TRUE' => [
        'args' => [TRUE],
        'expected' => ['1'],
      ],
      'positional args with bool FALSE (excluded)' => [
        'args' => [FALSE],
        'expected' => [],
      ],
      'mixed positional and named' => [
        'args' => ['pos1', '--opt' => 'val', 'pos2'],
        'expected' => ['pos1', '--opt=val', 'pos2'],
      ],
    ];
  }

}

/**
 * Concrete runner implementation for testing AbstractRunner.
 */
class ConcreteRunner extends AbstractRunner {

  /**
   * {@inheritdoc}
   */
  public function run(string $command, array $args = [], array $inputs = [], array $env = [], ?OutputInterface $output = NULL): static {
    // Simple implementation for testing.
    $this->command = $command;
    return $this;
  }

  /**
   * Public wrapper for parseCommand.
   */
  public function parseCommandPublic(string $command): array {
    return $this->parseCommand($command);
  }

  /**
   * Public wrapper for buildCommandString.
   */
  public function buildCommandStringPublic(string $command, array $args = [], array $opts = []): string {
    return $this->buildCommandString($command, $args, $opts);
  }

  /**
   * Public wrapper for quoteArgument.
   */
  public function quoteArgumentPublic(string $argument): string {
    return $this->quoteArgument($argument);
  }

  /**
   * Public wrapper for formatArgs.
   */
  public function formatArgsPublic(array $args): array {
    return $this->formatArgs($args);
  }

  /**
   * Public wrapper for reset.
   */
  public function resetPublic(): void {
    $this->reset();
  }

  /**
   * Public setter for command (for testing).
   */
  public function setCommand(string $command): void {
    $this->command = $command;
  }

  /**
   * Public setter for output (for testing).
   */
  public function setOutput(string $output): void {
    $this->output = $output;
  }

  /**
   * Public setter for exitCode (for testing).
   */
  public function setExitCode(int $exitCode): void {
    if ($exitCode < 0 || $exitCode > 255) {
      throw new \RuntimeException('Exit code is out of valid range (0-255).');
    }

    $this->exitCode = $exitCode;
  }

  /**
   * Public getter for shouldStream (for testing).
   */
  public function shouldStream(): bool {
    return $this->shouldStream;
  }

  /**
   * Public wrapper for initLogger.
   */
  public function initLoggerPublic(string $command, array $args = []): FileLoggerInterface {
    return $this->initLogger($command, $args);
  }

  /**
   * Public wrapper for resolveOutput.
   */
  public function resolveOutputPublic(?OutputInterface $output): OutputInterface {
    return $this->resolveOutput($output);
  }

}
