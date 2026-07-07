<?php

declare(strict_types=1);

namespace DrevOps\Tui\Config;

/**
 * A panel: an ordered group of fields and nested sub-panels.
 *
 * @package DrevOps\Tui\Config
 */
final readonly class Panel {

  /**
   * Construct a panel.
   *
   * @param string $id
   *   The unique panel id.
   * @param string $title
   *   The panel title.
   * @param string $description
   *   The panel description.
   * @param \DrevOps\Tui\Config\Field[] $fields
   *   Ordered fields in this panel.
   * @param \DrevOps\Tui\Config\Panel[] $panels
   *   Ordered nested sub-panels.
   */
  public function __construct(
    public string $id,
    public string $title,
    public string $description,
    public array $fields = [],
    public array $panels = [],
  ) {
  }

}
