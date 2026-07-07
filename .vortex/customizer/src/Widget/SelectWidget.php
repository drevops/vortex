<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\ThemeInterface;

/**
 * A single-choice radio list.
 *
 * @package DrevOps\Tui\Widget
 */
class SelectWidget extends AbstractWidget {

  /**
   * The option values in display order.
   *
   * @var list<string>
   */
  protected array $values;

  /**
   * The highlighted option index.
   */
  protected int $cursor = 0;

  /**
   * Construct a select widget.
   *
   * @param array<string,string> $labels
   *   Options as value => label, in display order.
   * @param string $default
   *   The initially highlighted value.
   * @param \Closure|null $validate
   *   Optional validator (see AbstractWidget).
   * @param \Closure|null $transform
   *   Optional transformer (see AbstractWidget).
   */
  public function __construct(protected array $labels, string $default = '', ?\Closure $validate = NULL, ?\Closure $transform = NULL) {
    parent::__construct($validate, $transform);
    $this->values = array_keys($this->labels);
    $index = array_search($default, $this->values, TRUE);
    $this->cursor = $index === FALSE ? 0 : $index;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Key $key): void {
    if ($this->handleCancel($key)) {
      return;
    }

    if ($key->is(KeyName::Up)) {
      $this->cursor = max(0, $this->cursor - 1);

      return;
    }

    if ($key->is(KeyName::Down)) {
      $this->cursor = min(count($this->values) - 1, $this->cursor + 1);

      return;
    }

    if ($key->is(KeyName::Enter)) {
      $this->accept($this->liveValue());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function liveValue(): mixed {
    return $this->values[$this->cursor] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function view(ThemeInterface $theme): string {
    $lines = [];

    foreach ($this->values as $index => $value) {
      $marker = $index === $this->cursor ? $theme->glyph('radio_on') : $theme->glyph('radio_off');
      $lines[] = $marker . ' ' . ($this->labels[$value] ?? $value);
    }

    return implode("\n", $lines);
  }

}
