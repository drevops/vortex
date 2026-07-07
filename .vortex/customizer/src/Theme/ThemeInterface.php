<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Theme;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\Panel;
use DrevOps\Customizer\Tui\Navigator;
use DrevOps\Customizer\Tui\Viewport;

/**
 * The contract a theme fulfils: style and glyph resolution plus TUI rendering.
 *
 * A theme is the single visual authority for the TUI. Implement this - usually
 * by extending AbstractTheme and defining a palette and a glyph set - to own
 * the colours, glyphs and how every element is composed.
 *
 * @package DrevOps\Customizer\Theme
 */
interface ThemeInterface {

  /**
   * Style text for a role.
   *
   * @param string $role
   *   The role name.
   * @param string $text
   *   The text.
   *
   * @return string
   *   The styled text (plain when colour is disabled).
   */
  public function style(string $role, string $text): string;

  /**
   * The SGR parameters for a role.
   *
   * @param string $role
   *   The role name.
   *
   * @return string
   *   The SGR parameters (empty when colour is off or the role is unknown).
   */
  public function sgr(string $role): string;

  /**
   * The glyph for a decorative element.
   *
   * @param string $name
   *   The glyph name.
   *
   * @return string
   *   The glyph (Unicode or ASCII per the theme's mode; empty when unknown).
   */
  public function glyph(string $name): string;

  /**
   * Whether colour is enabled.
   *
   * @return bool
   *   TRUE when colour is enabled.
   */
  public function hasColor(): bool;

  /**
   * Whether Unicode glyphs are enabled.
   *
   * @return bool
   *   TRUE for Unicode glyphs, FALSE for the ASCII fallback.
   */
  public function hasUnicode(): bool;

  /**
   * The number of navigable items in a panel (fields plus sub-panels).
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The panel.
   *
   * @return int
   *   The item count.
   */
  public function itemCount(Panel $panel): int;

  /**
   * Build the body lines and the line index of the selected item.
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The panel.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   * @param int $cursor
   *   The selected item index.
   *
   * @return array{list<string>,int}
   *   The body lines and the selected item's first line index.
   */
  public function body(Panel $panel, Answers $answers, int $cursor): array;

  /**
   * Render a field row.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function fieldLine(Field $field, Answers $answers, bool $selected): string;

  /**
   * Render a sub-panel row.
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The sub-panel.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function panelLine(Panel $panel, bool $selected): string;

  /**
   * Render a description row.
   *
   * @param string $description
   *   The description.
   *
   * @return string
   *   The row.
   */
  public function descriptionLine(string $description): string;

  /**
   * A one-line summary of a sub-panel's active field values, for the hub.
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The sub-panel.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   *
   * @return string
   *   The summary, or an empty string when the panel has no active fields.
   */
  public function panelSummary(Panel $panel, Answers $answers): string;

  /**
   * Render a sub-panel value-summary row.
   *
   * @param string $summary
   *   The summary text.
   *
   * @return string
   *   The row.
   */
  public function summaryLine(string $summary): string;

  /**
   * Render a breadcrumb line for the navigator.
   *
   * @param \DrevOps\Customizer\Tui\Navigator $navigator
   *   The navigator.
   *
   * @return string
   *   The breadcrumb line.
   */
  public function breadcrumbLine(Navigator $navigator): string;

  /**
   * Compose a frame: pinned header, scrolled body with indicators, footer.
   *
   * @param list<string> $header
   *   The header lines.
   * @param list<string> $body
   *   The body lines.
   * @param list<string> $footer
   *   The footer lines.
   * @param \DrevOps\Customizer\Tui\Viewport $viewport
   *   The viewport.
   * @param int $height
   *   The body viewport height.
   *
   * @return string
   *   The composed frame.
   */
  public function frame(array $header, array $body, array $footer, Viewport $viewport, int $height): string;

  /**
   * Compose a start banner: the logo above an optional version line.
   *
   * @param string $logo
   *   The banner logo (may be multi-line).
   * @param string $version
   *   The version string, shown dimmed below the logo when non-empty.
   *
   * @return string
   *   The composed banner.
   */
  public function banner(string $logo, string $version): string;

  /**
   * Render the status line shown at the foot of a panel.
   *
   * @return string
   *   The themed status line.
   */
  public function statusLine(): string;

  /**
   * Render a row of inline submit/cancel buttons.
   *
   * @param list<string> $labels
   *   The button labels.
   * @param int $selected
   *   The selected button index, or -1 for none.
   *
   * @return string
   *   The button row.
   */
  public function buttonBar(array $labels, int $selected): string;

}
