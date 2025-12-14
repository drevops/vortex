<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Task;

use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Output wrapper that dims all output for streaming tasks.
 */
class TaskOutput implements OutputInterface {

  public function __construct(
    protected OutputInterface $wrapped,
  ) {
  }

  /**
   * Writes a message to the output.
   *
   * @param string|iterable<int,string> $messages
   *   The message or messages to write.
   * @param bool $newline
   *   Whether to add a newline after the message.
   * @param int $options
   *   Write options.
   */
  public function write(string|iterable $messages, bool $newline = FALSE, int $options = 0): void {
    $dimmed = is_iterable($messages)
      ? array_map(Tui::dim(...), (array) $messages)
      : Tui::dim($messages);
    $this->wrapped->write($dimmed, $newline, $options);
  }

  /**
   * Writes a message to the output and adds a newline at the end.
   *
   * @param string|iterable<int,string> $messages
   *   The message or messages to write.
   * @param int $options
   *   Write options.
   */
  public function writeln(string|iterable $messages, int $options = 0): void {
    $dimmed = is_iterable($messages)
      ? array_map(Tui::dim(...), (array) $messages)
      : Tui::dim($messages);
    $this->wrapped->writeln($dimmed, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function setVerbosity(int $level): void {
    $this->wrapped->setVerbosity($level);
  }

  /**
   * {@inheritdoc}
   */
  public function getVerbosity(): int {
    return $this->wrapped->getVerbosity();
  }

  /**
   * {@inheritdoc}
   */
  public function isQuiet(): bool {
    return $this->wrapped->isQuiet();
  }

  /**
   * {@inheritdoc}
   */
  public function isVerbose(): bool {
    return $this->wrapped->isVerbose();
  }

  /**
   * {@inheritdoc}
   */
  public function isVeryVerbose(): bool {
    return $this->wrapped->isVeryVerbose();
  }

  /**
   * {@inheritdoc}
   */
  public function isDebug(): bool {
    return $this->wrapped->isDebug();
  }

  /**
   * {@inheritdoc}
   */
  public function setDecorated(bool $decorated): void {
    $this->wrapped->setDecorated($decorated);
  }

  /**
   * {@inheritdoc}
   */
  public function isDecorated(): bool {
    return $this->wrapped->isDecorated();
  }

  /**
   * {@inheritdoc}
   */
  public function setFormatter(OutputFormatterInterface $formatter): void {
    $this->wrapped->setFormatter($formatter);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatter(): OutputFormatterInterface {
    return $this->wrapped->getFormatter();
  }

}
