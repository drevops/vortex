<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Task;

use AlexSkrypnyk\Prompty\Prompty;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\StreamOutput;

class Task {

  public static function action(
    \Closure|string $label,
    ?\Closure $action = NULL,
    \Closure|string|null $hint = NULL,
    \Closure|string|null $success = NULL,
    \Closure|string|null $failure = NULL,
    bool $streaming = FALSE,
  ): mixed {
    $label = is_callable($label) ? $label() : $label;

    if (!is_callable($action)) {
      throw new \InvalidArgumentException('Action must be callable.');
    }

    // Prompty draws 1 task at a time, so a task inside a streamed task is
    // part of that task's output.
    if (Tui::isCaptured()) {
      return self::plain($label, $action, $hint, $success, $failure);
    }

    $output = Tui::output();
    $is_interactive = Tui::isInteractive();

    $run = function () use ($action, $output, $is_interactive, $streaming): mixed {
      if ($streaming) {
        Tui::capture(TRUE);
      }

      try {
        return $action();
      }
      finally {
        if ($streaming) {
          Tui::capture(FALSE);
        }

        // A command run by the action initializes the TUI with its own output,
        // so the result line needs this command's output back.
        Tui::init($output, $is_interactive);
      }
    };

    // Lines written to php://output go through the output buffer that a
    // streamed task captures.
    if ($streaming) {
      $stream = fopen('php://output', 'w');

      if ($stream === FALSE) {
        throw new \RuntimeException('Could not open php://output for a streamed task.');
      }

      Tui::setOutput(new StreamOutput($stream, $output->getVerbosity(), $output->isDecorated()));
    }

    try {
      return Prompty::task($label, action: $run, hint: $hint ?? '', success: $success ?? '', failure: $failure ?? '', stream: $streaming);
    }
    finally {
      Tui::setOutput($output);
    }
  }

  /**
   * Run an action and report it as plain lines.
   *
   * @param string $label
   *   The task label.
   * @param \Closure $action
   *   The action to run.
   * @param \Closure|string|null $hint
   *   The hint, or a callable that receives the action's return value.
   * @param \Closure|string|null $success
   *   The success message, or a callable that receives the return value.
   * @param \Closure|string|null $failure
   *   The failure message, or a callable that receives the return value.
   *
   * @return mixed
   *   The value the action returns.
   */
  protected static function plain(string $label, \Closure $action, \Closure|string|null $hint, \Closure|string|null $success, \Closure|string|null $failure): mixed {
    Tui::line($label, 0);

    $return = $action();

    $hint = is_callable($hint) ? $hint($return) : $hint;
    if ($hint) {
      Tui::line($hint, 2);
    }

    foreach (is_array($return) ? $return : [] as $detail) {
      Tui::line((string) $detail, 2);
    }

    $is_ok = $return !== FALSE;
    $message = $is_ok ? $success : $failure;
    $message = is_callable($message) ? $message($return) : $message;

    Tui::line(($is_ok ? '✓ ' : '✕ ') . ($message ?: ($is_ok ? 'OK' : 'FAILED')), 0);

    return $return;
  }

}
