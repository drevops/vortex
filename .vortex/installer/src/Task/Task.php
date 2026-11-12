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

    $output = Tui::output();
    $is_interactive = Tui::isInteractive();

    $run = function () use ($action, $output, $is_interactive): mixed {
      try {
        return $action();
      }
      finally {
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

}
