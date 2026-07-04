<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Handler;

/**
 * The run context passed to handler discover() and process().
 *
 * @package DrevOps\Customizer\Handler
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
   */
  public function __construct(
    public string $directory = '',
    public array $answers = [],
    public bool $update = FALSE,
  ) {
  }

}
