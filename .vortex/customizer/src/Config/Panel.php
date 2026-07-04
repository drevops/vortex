<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Config;

/**
 * A panel: an ordered group of fields and nested sub-panels.
 *
 * @package DrevOps\Customizer\Config
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
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   Ordered fields in this panel.
   * @param \DrevOps\Customizer\Config\Panel[] $panels
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
