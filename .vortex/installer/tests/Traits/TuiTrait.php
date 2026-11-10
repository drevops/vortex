<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Traits;

use AlexSkrypnyk\Prompty\PromptyTestTrait;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\BufferedOutput;

trait TuiTrait {

  use PromptyTestTrait;

  protected static function tuiSetUp(bool $is_interactive = FALSE): void {
    Tui::init(new BufferedOutput(), $is_interactive);
  }

  protected function tuiTeardown(): void {
    // Tui::init() configures a new Prompty instance in the next test.
    $this->promptyTearDown();
  }

  /**
   * Feed keystrokes to the prompts.
   *
   * A rejected answer throws instead of re-prompting, so a test can assert on
   * the validation message rather than on a prompt cancelled at the end of the
   * input.
   *
   * @param array<int, string> $keystrokes
   *   Keystrokes in the order the prompts read them.
   */
  protected function tuiInput(array $keystrokes): void {
    $stream = fopen('php://memory', 'r+');

    if ($stream === FALSE) {
      throw new \RuntimeException('Could not open a memory stream for the keystrokes.');
    }

    fwrite($stream, implode('', $keystrokes));
    rewind($stream);

    $instance = $this->promptyGetStatic('instance');

    if (!is_object($instance)) {
      throw new \RuntimeException('Prompty is not initialized. Call tuiSetUp() first.');
    }

    $this->promptySetProperty($instance, 'input', $stream);
    $this->promptyThrowOnInvalid();
  }

  /**
   * Helper to create command options array with '--' prefix.
   *
   * @param array<string, mixed> $options
   *   Array of option constants as keys and their values.
   *
   * @return array<string, mixed>
   *   Array with '--' prefix added to each option key.
   */
  protected static function tuiOptions(array $options): array {
    $result = [];
    foreach ($options as $option => $value) {
      $result['--' . $option] = $value;
    }
    return $result;
  }

}
