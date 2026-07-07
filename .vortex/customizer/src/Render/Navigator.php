<?php

declare(strict_types=1);

namespace DrevOps\Tui\Render;

use DrevOps\Tui\Config\Panel;

/**
 * Tracks recursive panel navigation: drill into sub-panels, Escape pops.
 *
 * @package DrevOps\Tui\Render
 */
class Navigator {

  /**
   * The ancestor panels, outermost first.
   *
   * @var \DrevOps\Tui\Config\Panel[]
   */
  protected array $parents = [];

  /**
   * Construct a navigator at a root panel.
   *
   * @param \DrevOps\Tui\Config\Panel $current
   *   The root (hub) panel.
   */
  public function __construct(protected Panel $current) {
  }

  /**
   * The current panel.
   *
   * @return \DrevOps\Tui\Config\Panel
   *   The current panel.
   */
  public function current(): Panel {
    return $this->current;
  }

  /**
   * Drill into a sub-panel.
   *
   * @param \DrevOps\Tui\Config\Panel $panel
   *   The panel to enter.
   */
  public function enter(Panel $panel): void {
    $this->parents[] = $this->current;
    $this->current = $panel;
  }

  /**
   * Pop back to the parent panel.
   *
   * @return bool
   *   TRUE when popped; FALSE when already at the root.
   */
  public function pop(): bool {
    $parent = array_pop($this->parents);
    if (!$parent instanceof Panel) {
      return FALSE;
    }

    $this->current = $parent;

    return TRUE;
  }

  /**
   * The nesting depth (1 at the root).
   *
   * @return int
   *   The depth.
   */
  public function depth(): int {
    return count($this->parents) + 1;
  }

  /**
   * Whether the navigator is at the root.
   *
   * @return bool
   *   TRUE at the root.
   */
  public function isRoot(): bool {
    return $this->parents === [];
  }

  /**
   * The breadcrumb of panel titles from the root to the current panel.
   *
   * @return list<string>
   *   The titles.
   */
  public function breadcrumb(): array {
    $titles = [];
    foreach ($this->parents as $parent) {
      $titles[] = $parent->title;
    }

    $titles[] = $this->current->title;

    return $titles;
  }

}
