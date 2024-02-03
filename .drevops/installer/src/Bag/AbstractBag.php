<?php

namespace DrevOps\Installer\Bag;

/**
 * Class AbstractBag.
 *
 * Bag to store and manipulate items.
 */
abstract class AbstractBag {

  /**
   * Array of items to store.
   *
   * @var array
   *   Associative array of items.
   */
  protected $items = [];

  /**
   * Get item.
   *
   * @param string $name
   *   Item name.
   * @param null $default
   *   Default value.
   *
   * @return mixed|null
   *   Item value or NULL if item does not exist.
   */
  public function get(string $name, $default = NULL): mixed {
    return $this->items[$name] ?? $default;
  }

  /**
   * Set item.
   *
   * @param string $name
   *   Item name.
   * @param mixed $value
   *   Item value.
   */
  public function set(string $name, mixed $value): void {
    $this->items[$name] = $value;
  }

  /**
   * Get all items.
   *
   * @return array
   *   Associative array of items.
   */
  public function getAll(): array {
    return $this->items;
  }

  /**
   * Import items from array.
   *
   * @param array $values
   *   Associative array of items.
   *
   * @return AbstractBag
   *   The current instance.
   */
  public function fromValues($values = []): AbstractBag {
    foreach ($values as $key => $value) {
      $this->set($key, $value);
    }

    return $this;
  }

  /**
   * Clear all items.
   *
   * @return AbstractBag
   *   The current instance.
   */
  public function clear(): AbstractBag {
    $this->items = [];

    return $this;
  }

}
