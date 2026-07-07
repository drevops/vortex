<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Widget;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use DrevOps\Customizer\Theme\Theme;

/**
 * Single-line text input with a movable cursor.
 *
 * @package DrevOps\Customizer\Widget
 */
class TextWidget extends AbstractWidget {

  /**
   * The cursor offset within the buffer.
   */
  protected int $cursor;

  /**
   * Construct a text widget.
   *
   * @param string $buffer
   *   The initial value (and live input buffer).
   * @param \Closure|null $validate
   *   Optional validator (see AbstractWidget).
   * @param \Closure|null $transform
   *   Optional transformer (see AbstractWidget).
   */
  public function __construct(protected string $buffer = '', ?\Closure $validate = NULL, ?\Closure $transform = NULL) {
    parent::__construct($validate, $transform);
    $this->cursor = strlen($this->buffer);
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Key $key): void {
    if ($this->handleCancel($key)) {
      return;
    }

    if ($key->is(KeyName::Enter)) {
      $this->accept($this->buffer);

      return;
    }

    if ($key->is(KeyName::Backspace)) {
      $this->backspace();

      return;
    }

    if ($key->is(KeyName::Left)) {
      $this->cursor = max(0, $this->cursor - 1);

      return;
    }

    if ($key->is(KeyName::Right)) {
      $this->cursor = min(strlen($this->buffer), $this->cursor + 1);

      return;
    }

    if ($key->is(KeyName::Space)) {
      $this->insert(' ');

      return;
    }

    if ($key->isChar()) {
      $this->insert($key->char ?? '');
    }
  }

  /**
   * Insert text at the cursor.
   *
   * @param string $char
   *   The text to insert.
   */
  protected function insert(string $char): void {
    $this->buffer = substr($this->buffer, 0, $this->cursor) . $char . substr($this->buffer, $this->cursor);
    $this->cursor += strlen($char);
  }

  /**
   * Delete the character before the cursor.
   */
  protected function backspace(): void {
    if ($this->cursor > 0) {
      $this->buffer = substr($this->buffer, 0, $this->cursor - 1) . substr($this->buffer, $this->cursor);
      $this->cursor--;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function liveValue(): mixed {
    return $this->buffer;
  }

  /**
   * {@inheritdoc}
   */
  public function view(Theme $theme): string {
    $line = substr($this->buffer, 0, $this->cursor) . $theme->glyph('caret') . substr($this->buffer, $this->cursor);

    return $this->error === NULL ? $line : $line . "\n" . $this->error;
  }

}
