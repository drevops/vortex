<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Handler;

/**
 * Resolves a field id to a consumer-provided handler by class name.
 *
 * A field id in snake_case is mapped to a PascalCase class name (e.g.
 * "machine_name" -> "MachineName") and looked up in the registered handler
 * namespaces, in order. Handlers are optional: a field with no handler uses
 * the engine's default behaviour.
 *
 * @package DrevOps\Customizer\Handler
 */
class HandlerRegistry {

  /**
   * The registered handler namespaces, searched in order.
   *
   * @var string[]
   */
  protected array $namespaces = [];

  /**
   * Resolved handlers, keyed by field id (NULL means "none registered").
   *
   * @var array<string,\DrevOps\Customizer\Handler\HandlerInterface|null>
   */
  protected array $cache = [];

  /**
   * Construct a registry.
   *
   * @param string[] $namespaces
   *   Handler namespaces to search, in order.
   */
  public function __construct(array $namespaces = []) {
    foreach ($namespaces as $namespace) {
      $this->addNamespace($namespace);
    }
  }

  /**
   * Register a handler namespace to search.
   *
   * @param string $namespace
   *   The namespace (with or without surrounding backslashes).
   */
  public function addNamespace(string $namespace): void {
    $this->namespaces[] = trim($namespace, '\\');
    $this->cache = [];
  }

  /**
   * Resolve the handler for a field id, or NULL if none is registered.
   *
   * @param string $field_id
   *   The field id (e.g. "machine_name").
   */
  public function get(string $field_id): ?HandlerInterface {
    if (array_key_exists($field_id, $this->cache)) {
      return $this->cache[$field_id];
    }

    $class = $this->classNameFor($field_id);
    foreach ($this->namespaces as $namespace) {
      $fqcn = $namespace . '\\' . $class;
      if (class_exists($fqcn) && is_a($fqcn, HandlerInterface::class, TRUE)) {
        return $this->cache[$field_id] = new $fqcn();
      }
    }

    return $this->cache[$field_id] = NULL;
  }

  /**
   * Resolve the handler for a field id, or throw if none is registered.
   *
   * @param string $field_id
   *   The field id.
   */
  public function getOrFail(string $field_id): HandlerInterface {
    $handler = $this->get($field_id);
    if (!$handler instanceof HandlerInterface) {
      throw new HandlerException(sprintf(
        'No handler found for field "%s" (expected class "%s" in one of: %s).',
        $field_id,
        $this->classNameFor($field_id),
        $this->namespaces === [] ? '(none)' : implode(', ', $this->namespaces),
      ));
    }

    return $handler;
  }

  /**
   * Convert a field id (snake_case) to a PascalCase class name.
   *
   * @param string $field_id
   *   The field id.
   */
  protected function classNameFor(string $field_id): string {
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $field_id)));
  }

}
