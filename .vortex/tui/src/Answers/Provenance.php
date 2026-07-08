<?php

declare(strict_types=1);

namespace DrevOps\Tui\Answers;

/**
 * How an answer's value came to be.
 *
 * @package DrevOps\Tui\Answers
 */
enum Provenance: string {

  /**
   * The declared (or type) default; nothing supplied or computed it.
   */
  case Default = 'default';

  /**
   * Detected from the project content in update mode.
   */
  case Detected = 'detected';

  /**
   * Supplied by the user: an input, an env override or an interactive edit.
   */
  case Edited = 'edited';

  /**
   * Computed by the question's derive rule.
   */
  case Derived = 'derived';

  /**
   * Supplied by the user over a derive rule, pinning the derived value.
   */
  case Override = 'override';

}
