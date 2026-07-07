<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use DrevOps\Customizer\Tui\Theme;

/**
 * A checkbox list with type-to-filter and select-all/none.
 *
 * Printable characters narrow the list; Space toggles the highlighted option;
 * Right selects all visible options and Left deselects them.
 *
 * @package DrevOps\Customizer\Widget
 */
class MultiSelectWidget extends AbstractWidget {

  /**
   * The option values in display order.
   *
   * @var list<string>
   */
  protected array $values;

  /**
   * The selected values as a set (value => TRUE).
   *
   * @var array<string,bool>
   */
  protected array $selected = [];

  /**
   * The current type-to-filter text.
   */
  protected string $filter = '';

  /**
   * The highlighted index within the visible (filtered) options.
   */
  protected int $cursor = 0;

  /**
   * Construct a multiselect widget.
   *
   * @param array<string,string> $labels
   *   Options as value => label, in display order.
   * @param list<string> $default
   *   The initially selected values.
   * @param \Closure|null $validate
   *   Optional validator (see AbstractWidget).
   * @param \Closure|null $transform
   *   Optional transformer (see AbstractWidget).
   */
  public function __construct(protected array $labels, array $default = [], ?\Closure $validate = NULL, ?\Closure $transform = NULL) {
    parent::__construct($validate, $transform);
    $this->values = array_keys($this->labels);

    foreach ($default as $value) {
      $this->selected[$value] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Key $key): void {
    if ($this->handleCancel($key)) {
      return;
    }

    if ($key->is(KeyName::Enter)) {
      $this->accept($this->liveValue());

      return;
    }

    if ($key->is(KeyName::Up)) {
      $this->cursor = max(0, $this->cursor - 1);

      return;
    }

    if ($key->is(KeyName::Down)) {
      $this->cursor = min(count($this->visible()) - 1, $this->cursor + 1);

      return;
    }

    if ($key->is(KeyName::Space)) {
      $this->toggleCurrent();

      return;
    }

    if ($key->is(KeyName::Right)) {
      $this->setAllVisible(TRUE);

      return;
    }

    if ($key->is(KeyName::Left)) {
      $this->setAllVisible(FALSE);

      return;
    }

    if ($key->is(KeyName::Backspace)) {
      $this->filter = substr($this->filter, 0, -1);
      $this->cursor = 0;

      return;
    }

    if ($key->isChar()) {
      $this->filter .= $key->char ?? '';
      $this->cursor = 0;
    }
  }

  /**
   * The options currently visible under the filter.
   *
   * @return list<string>
   *   The visible option values.
   */
  protected function visible(): array {
    if ($this->filter === '') {
      return $this->values;
    }

    $needle = strtolower($this->filter);

    return array_values(array_filter($this->values, fn(string $value): bool => str_contains(strtolower($this->labels[$value] ?? $value), $needle)));
  }

  /**
   * Toggle the highlighted option.
   */
  protected function toggleCurrent(): void {
    $visible = $this->visible();
    $value = $visible[$this->cursor] ?? NULL;

    if ($value === NULL) {
      return;
    }

    if (isset($this->selected[$value])) {
      unset($this->selected[$value]);
    }
    else {
      $this->selected[$value] = TRUE;
    }
  }

  /**
   * Select or deselect all visible options.
   *
   * @param bool $selected
   *   TRUE to select, FALSE to deselect.
   */
  protected function setAllVisible(bool $selected): void {
    foreach ($this->visible() as $value) {
      if ($selected) {
        $this->selected[$value] = TRUE;
      }
      else {
        unset($this->selected[$value]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function liveValue(): mixed {
    return array_values(array_filter($this->values, fn(string $value): bool => isset($this->selected[$value])));
  }

  /**
   * {@inheritdoc}
   */
  public function view(Theme $theme): string {
    $lines = [];

    foreach ($this->visible() as $index => $value) {
      $box = isset($this->selected[$value]) ? $theme->glyph('check_on') : $theme->glyph('check_off');
      $marker = $index === $this->cursor ? $theme->glyph('marker') : ' ';
      $lines[] = $marker . ' ' . $box . ' ' . ($this->labels[$value] ?? $value);
    }

    return implode("\n", $lines);
  }

}
