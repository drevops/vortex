<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

use Symfony\Component\Console\Terminal as ConsoleTerminal;

/**
 * Thin terminal I/O: raw mode, alternate screen, mouse, render and restore.
 *
 * The raw-mode toggling and input reading touch the real TTY and are excluded
 * from coverage; the output writing is stream-injectable and testable.
 *
 * @package DrevOps\Customizer\Tui
 */
class Terminal {

  /**
   * The output stream.
   *
   * @var resource
   */
  protected $output;

  /**
   * The input stream.
   *
   * @var resource
   */
  protected $input;

  /**
   * Construct a terminal.
   *
   * @param mixed $output
   *   The output stream (defaults to STDOUT).
   * @param mixed $input
   *   The input stream (defaults to STDIN).
   */
  public function __construct(mixed $output = NULL, mixed $input = NULL) {
    $this->output = is_resource($output) ? $output : STDOUT;
    $this->input = is_resource($input) ? $input : STDIN;
  }

  /**
   * Write text to the output.
   *
   * @param string $text
   *   The text.
   */
  public function write(string $text): void {
    fwrite($this->output, $text);
  }

  /**
   * Clear the screen and write a frame.
   *
   * @param string $frame
   *   The frame.
   */
  public function render(string $frame): void {
    $this->write(TerminalControl::clear() . $frame);
  }

  /**
   * Clear the screen.
   */
  public function clear(): void {
    $this->write(TerminalControl::clear());
  }

  /**
   * Enter the full-screen raw-input mode.
   */
  public function setup(): void {
    // @codeCoverageIgnoreStart
    $this->stty('-echo -icanon');
    $this->write(TerminalControl::altScreenOn() . TerminalControl::hideCursor() . TerminalControl::mouseOn());
    // @codeCoverageIgnoreEnd
  }

  /**
   * Restore the terminal to its normal mode.
   */
  public function restore(): void {
    // @codeCoverageIgnoreStart
    $this->write(TerminalControl::restore());
    $this->stty('sane');
    // @codeCoverageIgnoreEnd
  }

  /**
   * Read raw bytes from the input.
   *
   * @param int $bytes
   *   The maximum number of bytes to read.
   *
   * @return string
   *   The bytes read.
   */
  public function read(int $bytes = 32): string {
    // @codeCoverageIgnoreStart
    $data = fread($this->input, max(1, $bytes));

    return $data === FALSE ? '' : $data;
    // @codeCoverageIgnoreEnd
  }

  /**
   * The terminal height in rows.
   *
   * @return int
   *   The number of rows available for rendering.
   */
  public function height(): int {
    return (new ConsoleTerminal())->getHeight();
  }

  /**
   * Run stty with the given arguments.
   *
   * @param string $args
   *   The stty arguments.
   */
  protected function stty(string $args): void {
    // @codeCoverageIgnoreStart
    exec('stty ' . $args . ' 2>/dev/null');
    // @codeCoverageIgnoreEnd
  }

}
