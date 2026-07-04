<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;

/**
 * An autocomplete text input filtering a fixed option set.
 *
 * @package DrevOps\Customizer\Widget
 */
class SuggestWidget extends AbstractWidget {

  /**
   * The highlighted suggestion index, or -1 for none.
   */
  protected int $highlight = -1;

  /**
   * Construct a suggest widget.
   *
   * @param list<string> $values
   *   The suggestion values.
   * @param string $buffer
   *   The initial input.
   * @param \Closure|null $validate
   *   Optional validator (see AbstractWidget).
   * @param \Closure|null $transform
   *   Optional transformer (see AbstractWidget).
   */
  public function __construct(protected array $values, protected string $buffer = '', ?\Closure $validate = NULL, ?\Closure $transform = NULL) {
    parent::__construct($validate, $transform);
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

    if ($key->is(KeyName::Down)) {
      $this->highlight = min(count($this->matches()) - 1, $this->highlight + 1);

      return;
    }

    if ($key->is(KeyName::Up)) {
      $this->highlight = max(-1, $this->highlight - 1);

      return;
    }

    if ($key->is(KeyName::Backspace)) {
      $this->buffer = substr($this->buffer, 0, -1);
      $this->highlight = -1;

      return;
    }

    if ($key->is(KeyName::Space)) {
      $this->buffer .= ' ';
      $this->highlight = -1;

      return;
    }

    if ($key->isChar()) {
      $this->buffer .= $key->char ?? '';
      $this->highlight = -1;
    }
  }

  /**
   * The suggestions matching the current buffer.
   *
   * @return list<string>
   *   The matching suggestion values.
   */
  protected function matches(): array {
    if ($this->buffer === '') {
      return $this->values;
    }

    $needle = strtolower($this->buffer);

    return array_values(array_filter($this->values, fn(string $value): bool => str_contains(strtolower($value), $needle)));
  }

  /**
   * {@inheritdoc}
   */
  protected function liveValue(): mixed {
    if ($this->highlight >= 0) {
      $matches = $this->matches();

      return $matches[$this->highlight] ?? $this->buffer;
    }

    return $this->buffer;
  }

  /**
   * {@inheritdoc}
   */
  public function view(): string {
    $lines = [$this->buffer . '|'];

    foreach ($this->matches() as $index => $value) {
      $marker = $index === $this->highlight ? '>' : ' ';
      $lines[] = $marker . ' ' . $value;
    }

    return implode("\n", $lines);
  }

}
