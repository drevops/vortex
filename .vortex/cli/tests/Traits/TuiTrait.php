<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Traits;

use DrevOps\VortexCli\Utils\Tui;
use Symfony\Component\Console\Output\BufferedOutput;

trait TuiTrait {

  const TUI_MAX_QUESTIONS = 25;

  protected static function tuiSetUp(): void {
    // Headless: the form engine rejects an invalid answer outright rather than
    // re-asking, so a test never waits on input it cannot give.
    Tui::init(new BufferedOutput(), FALSE);
  }

  protected static function tuiTeardown(): void {
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
