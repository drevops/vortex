<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Input;

/**
 * A single key press: either a named special key or a printable character.
 *
 * @package DrevOps\Customizer\Input
 */
final readonly class Key {

  /**
   * Construct a key.
   *
   * @param \DrevOps\Customizer\Input\KeyName|null $name
   *   The named special key, or NULL for a printable character.
   * @param string|null $char
   *   The printable character, or NULL for a named key.
   */
  protected function __construct(
    public ?KeyName $name = NULL,
    public ?string $char = NULL,
  ) {
  }

  /**
   * Create a named special key.
   *
   * @param \DrevOps\Customizer\Input\KeyName $name
   *   The key name.
   */
  public static function named(KeyName $name): self {
    return new self($name);
  }

  /**
   * Create a printable-character key.
   *
   * @param string $char
   *   A single printable character.
   */
  public static function char(string $char): self {
    return new self(NULL, $char);
  }

  /**
   * Whether this key is a printable character.
   */
  public function isChar(): bool {
    return $this->char !== NULL;
  }

  /**
   * Whether this key is the given named key.
   *
   * @param \DrevOps\Customizer\Input\KeyName $name
   *   The name to compare.
   */
  public function is(KeyName $name): bool {
    return $this->name === $name;
  }

}
