<?php

declare(strict_types=1);

namespace DrevOps\Tui\Input;

/**
 * An in-memory key stream, used for scripted (headless) input.
 *
 * @package DrevOps\Tui\Input
 */
final class ArrayKeyStream implements KeyStreamInterface {

  /**
   * The queued keys.
   *
   * @var \DrevOps\Tui\Input\Key[]
   */
  protected array $keys;

  /**
   * The read cursor.
   */
  protected int $position = 0;

  /**
   * Construct a stream from a list of keys.
   *
   * @param \DrevOps\Tui\Input\Key[] $keys
   *   The keys to queue.
   */
  public function __construct(array $keys) {
    $this->keys = array_values($keys);
  }

  /**
   * Build a stream from printable strings and named keys.
   *
   * Each string expands to one character key per character; each Key is queued
   * as-is, letting tests write `of('Acme', Key::named(KeyName::Enter))`.
   *
   * @param string|\DrevOps\Tui\Input\Key ...$items
   *   Strings (expanded per character) and/or keys.
   */
  public static function of(string|Key ...$items): self {
    $keys = [];

    foreach ($items as $item) {
      if ($item instanceof Key) {
        $keys[] = $item;
        continue;
      }

      if ($item === '') {
        continue;
      }

      foreach (str_split($item) as $char) {
        $keys[] = Key::char($char);
      }
    }

    return new self($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function read(): ?Key {
    return $this->keys[$this->position++] ?? NULL;
  }

}
