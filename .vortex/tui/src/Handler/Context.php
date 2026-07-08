<?php

declare(strict_types=1);

namespace DrevOps\Tui\Handler;

/**
 * The run context passed to the handler hooks.
 *
 * @package DrevOps\Tui\Handler
 */
final readonly class Context {

  /**
   * Construct a context.
   *
   * @param string $directory
   *   The destination project directory.
   * @param array<string,mixed> $answers
   *   The collected answers, keyed by field id.
   * @param bool $update
   *   Whether the run targets an existing project (enables discovery).
   * @param string $version
   *   The version string used to stamp version placeholders.
   * @param string $destination
   *   The final destination directory, used to carry existing project state.
   */
  public function __construct(
    public string $directory = '',
    public array $answers = [],
    public bool $update = FALSE,
    public string $version = '',
    public string $destination = '',
  ) {
  }

}
