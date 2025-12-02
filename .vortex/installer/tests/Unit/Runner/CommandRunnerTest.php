<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Runner;

use DrevOps\VortexInstaller\Logger\FileLogger;
use DrevOps\VortexInstaller\Runner\CommandRunner;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for CommandRunner class.
 */
#[CoversClass(CommandRunner::class)]
class CommandRunnerTest extends UnitTestCase {

  /**
   * Test constructor accepts Application instance.
   */
  public function testConstructor(): void {
    $application = new Application();
    $runner = new CommandRunner($application);

    $this->assertInstanceOf(CommandRunner::class, $runner);
  }

  /**
   * Test run with valid command.
   */
  public function testRunWithValidCommand(): void {
    $application = new Application();
    $command = new TestCommand('test:command');
    $application->add($command);

    $runner = new CommandRunner($application);
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    Tui::init($output);

    $result = $runner->run('test:command');

    $this->assertInstanceOf(CommandRunner::class, $result);
    $this->assertEquals(0, $runner->getExitCode());
    $runner_output = $runner->getOutput();
    $this->assertStringContainsString('Test output', is_string($runner_output) ? $runner_output : implode(PHP_EOL, $runner_output));
  }

  /**
   * Test run with streaming enabled/disabled.
   */
  #[DataProvider('dataProviderRunWithStreaming')]
  public function testRunWithStreaming(bool $streaming_enabled, bool $should_have_output): void {
    $application = new Application();
    $command = new TestCommand('test:command');
    $application->add($command);

    $runner = new CommandRunner($application);
    $runner->setCwd(self::$tmp);

    if (!$streaming_enabled) {
      $runner->disableStreaming();
    }

    $output = new BufferedOutput();
    Tui::init($output);

    $runner->run('test:command', [], [], [], $output);

    $stream_content = $output->fetch();

    if ($should_have_output) {
      $this->assertStringContainsString('Test output', $stream_content);
    }
    else {
      $this->assertStringNotContainsString('Test output', $stream_content);
    }

    // Output should always be captured in runner.
    $output = $runner->getOutput();
    $this->assertStringContainsString('Test output', is_string($output) ? $output : implode(PHP_EOL, $output));
  }

  /**
   * Test createCompositeOutput method using reflection.
   */
  public function testCreateCompositeOutput(): void {
    $application = new Application();
    $runner = new CommandRunner($application);
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    $logger = new FileLogger();
    $logger->setDir(self::$tmp);
    $logger->open('test');

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($runner);
    $method = $reflection->getMethod('createCompositeOutput');

    [$composite_output, $buffered_output] = $method->invoke($runner, $output, $logger);

    $this->assertInstanceOf(OutputInterface::class, $composite_output);
    $this->assertInstanceOf(BufferedOutput::class, $buffered_output);

    // Test composite output behavior.
    $composite_output->write('Test message');
    $this->assertStringContainsString('Test message', $buffered_output->fetch());

    $composite_output->writeln('Another line');
    $this->assertStringContainsString('Another line', $buffered_output->fetch());

    $logger->close();
  }

  /**
   * Test composite output with iterable messages.
   */
  public function testCompositeOutputWithIterableMessages(): void {
    $application = new Application();
    $runner = new CommandRunner($application);
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    $logger = new FileLogger();
    $logger->setDir(self::$tmp);
    $logger->open('test');

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($runner);
    $method = $reflection->getMethod('createCompositeOutput');

    [$composite_output, $buffered_output] = $method->invoke($runner, $output, $logger);

    // Test with iterable messages.
    $composite_output->write(['Line 1', 'Line 2']);
    $content = $buffered_output->fetch();
    $this->assertStringContainsString('Line 1', $content);
    $this->assertStringContainsString('Line 2', $content);

    $composite_output->writeln(['Line 3', 'Line 4']);
    $content = $buffered_output->fetch();
    $this->assertStringContainsString('Line 3', $content);
    $this->assertStringContainsString('Line 4', $content);

    $logger->close();
  }

  /**
   * Test run with options.
   */
  public function testRunWithOptions(): void {
    $application = new Application();
    $command = new TestCommand('test:command');
    $application->add($command);

    $runner = new CommandRunner($application);
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    Tui::init($output);

    // Test without options since test command doesn't define any.
    $runner->run('test:command', []);

    $this->assertEquals(0, $runner->getExitCode());
  }

  /**
   * Test run captures exit code.
   */
  public function testRunCapturesExitCode(): void {
    $application = new Application();
    $command = new TestCommandWithExitCode('test:error');
    $application->add($command);

    $runner = new CommandRunner($application);
    $runner->setCwd(self::$tmp);

    $output = new BufferedOutput();
    Tui::init($output);

    $runner->run('test:error');

    $this->assertEquals(1, $runner->getExitCode());
  }

  /**
   * Data provider for streaming modes.
   */
  public static function dataProviderRunWithStreaming(): array {
    return [
      'streaming enabled' => [
        'streaming_enabled' => TRUE,
        'should_have_output' => TRUE,
      ],
      'streaming disabled' => [
        'streaming_enabled' => FALSE,
        'should_have_output' => FALSE,
      ],
    ];
  }

}

/**
 * Test command for testing CommandRunner.
 */
class TestCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Test output');
    return 0;
  }

}

/**
 * Test command that returns non-zero exit code.
 */
class TestCommandWithExitCode extends Command {

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Error output');
    return 1;
  }

}
