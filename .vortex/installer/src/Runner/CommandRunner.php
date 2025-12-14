<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

use DrevOps\VortexInstaller\Logger\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runner for Symfony Console sub-commands.
 */
class CommandRunner extends AbstractRunner {

  /**
   * CommandRunner constructor.
   *
   * @param \Symfony\Component\Console\Application $application
   *   The application instance to find commands.
   */
  public function __construct(protected Application $application) {
  }

  /**
   * {@inheritdoc}
   *
   * For Symfony Console commands, the $inputs parameter is used for options
   * (named arguments like --verbose or --format=json).
   */
  public function run(string $command, array $args = [], array $inputs = [], array $env = [], ?OutputInterface $output = NULL): self {
    $this->reset();

    // Merge args and inputs (options) for ArrayInput.
    $input_args = array_merge($args, $inputs);
    $this->command = $this->buildCommandString($command, $args, $inputs);

    // Validate command existence and prepare input (also validated).
    $symfony_command = $this->application->find($command);
    $input = new ArrayInput($input_args);

    $positional_args = array_values(array_filter($args, is_int(...), ARRAY_FILTER_USE_KEY));
    $logger = $this->initLogger($command, $positional_args);

    $output = $this->resolveOutput($output);

    // Create composite output that captures, streams, and logs.
    [$composite_output, $buffered_output] = $this->createCompositeOutput($output, $logger);

    $exit_code = $symfony_command->run($input, $composite_output);

    if ($exit_code < 0 || $exit_code > 255) {
      throw new \RuntimeException('Command exited with invalid exit code: ' . $exit_code);
    }

    match ($exit_code) {
      Command::SUCCESS => $this->exitCode = self::EXIT_SUCCESS,
      Command::FAILURE => $this->exitCode = self::EXIT_FAILURE,
      127 => $this->exitCode = self::EXIT_COMMAND_NOT_FOUND,
      default => $this->exitCode = self::EXIT_INVALID,
    };

    $this->exitCode = $exit_code;
    $this->output = $buffered_output->fetch();

    $logger->close();

    return $this;
  }

  /**
   * Create a composite output that captures, streams, and logs.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output interface to stream to.
   * @param \DrevOps\VortexInstaller\Logger\LoggerInterface $logger
   *   The logger to write to.
   *
   * @return array{0: \Symfony\Component\Console\Output\OutputInterface, 1: \Symfony\Component\Console\Output\BufferedOutput}
   *   Array with [composite_output, buffered_output].
   */
  protected function createCompositeOutput(OutputInterface $output, LoggerInterface $logger): array {
    $buffered_output = new BufferedOutput();

    $composite_output = new class($buffered_output, $output, $logger, $this->shouldStream) extends BufferedOutput {

      public function __construct(
        private readonly BufferedOutput $bufferedOutput,
        private readonly OutputInterface $output,
        private readonly LoggerInterface $logger,
        private readonly bool $shouldStream,
      ) {
        parent::__construct();
      }

      /**
       * Write a message to the output and log.
       *
       * @param string|iterable<int,string> $messages
       *   The message or messages to write.
       * @param bool $newline
       *   Whether to add a newline after the message.
       * @param int $options
       *   Write options.
       */
      public function write(string|iterable $messages, bool $newline = FALSE, int $options = 0): void {
        $this->bufferedOutput->write($messages, $newline, $options);

        if ($this->shouldStream) {
          $this->output->write($messages, $newline, $options);
        }

        $text = is_iterable($messages) ? implode($newline ? PHP_EOL : '', (array) $messages) : $messages;
        $this->logger->write($text . ($newline ? PHP_EOL : ''));
      }

      /**
       * Write a message with a newline to the output and log.
       *
       * @param string|iterable<int,string> $messages
       *   The message or messages to write.
       * @param int $options
       *   Write options.
       */
      public function writeln(string|iterable $messages, int $options = 0): void {
        $this->bufferedOutput->writeln($messages, $options);
        if ($this->shouldStream) {
          $this->output->writeln($messages, $options);
        }
        $text = is_iterable($messages) ? implode(PHP_EOL, (array) $messages) : $messages;
        $this->logger->write($text . PHP_EOL);
      }

      public function fetch(): string {
        return $this->bufferedOutput->fetch();
      }

    };

    return [$composite_output, $buffered_output];
  }

}
