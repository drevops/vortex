<?php

declare(strict_types=1);

namespace DrevOps\Tui\Widget;

use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\ThemeInterface;

/**
 * Multi-line text input: Enter inserts a newline, Tab accepts.
 *
 * @package DrevOps\Tui\Widget
 */
class TextareaWidget extends TextWidget {

  /**
   * {@inheritdoc}
   */
  public function handle(Key $key): void {
    if ($key->is(KeyName::Enter)) {
      $this->insert("\n");

      return;
    }

    if ($key->is(KeyName::Tab)) {
      $this->accept($this->liveValue());

      return;
    }

    if ($key->is(KeyName::Up)) {
      $this->moveLine(-1);

      return;
    }

    if ($key->is(KeyName::Down)) {
      $this->moveLine(1);

      return;
    }

    parent::handle($key);
  }

  /**
   * Move the cursor to the adjacent line, keeping the column when possible.
   *
   * @param int $delta
   *   The line offset: -1 for up, 1 for down.
   */
  protected function moveLine(int $delta): void {
    $lines = explode("\n", $this->buffer);

    $line = 0;
    $column = $this->cursor;
    foreach ($lines as $index => $text) {
      $length = strlen($text);

      if ($column <= $length) {
        $line = $index;
        break;
      }

      // Skip the line and its trailing newline.
      $column -= $length + 1;
    }

    $target = $line + $delta;

    if ($target < 0 || $target >= count($lines)) {
      return;
    }

    $offset = 0;
    for ($index = 0; $index < $target; $index++) {
      $offset += strlen($lines[$index]) + 1;
    }

    $this->cursor = $offset + min($column, strlen($lines[$target]));
  }

  /**
   * {@inheritdoc}
   */
  public function view(ThemeInterface $theme): string {
    $text = substr($this->buffer, 0, $this->cursor) . $theme->glyph('caret') . substr($this->buffer, $this->cursor);
    $hint = $theme->style('footer', 'enter newline ' . $theme->glyph('dot') . ' tab accept');

    $out = $text . "\n" . $hint;

    return $this->error === NULL ? $out : $out . "\n" . $this->error;
  }

}
