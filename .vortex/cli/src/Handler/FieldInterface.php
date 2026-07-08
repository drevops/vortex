<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;

/**
 * A handler declaring its own form field.
 *
 * The form stays a table of contents - panels, panel labels and question
 * order - while everything about a single question (label, description,
 * default, options, rules) is declared here, next to its constants, reusable
 * behaviour and processing.
 *
 * @package DrevOps\VortexCli\Handler
 */
interface FieldInterface {

  /**
   * Declare the handler's field on a panel.
   *
   * @param \DrevOps\Tui\Builder\PanelBuilder $p
   *   The panel builder to declare the field on.
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The declared field.
   */
  public static function field(PanelBuilder $p): FieldBuilder;

}
