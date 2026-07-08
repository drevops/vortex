<?php

declare(strict_types=1);

namespace DrevOps\Tui\Handler;

/**
 * Resolves a consumer's per-field class and its reusable static behaviour.
 *
 * A field id in snake_case is mapped to a PascalCase class name (e.g.
 * "machine_name" -> "MachineName") and looked up in the registered
 * namespaces, in order. The class is the consumer's own - typically its
 * processor for the field. When it declares a public static validate() or
 * transform(), the engine uses them as the field's reusable behaviour unless
 * the form declares its own closure, which always wins.
 *
 * @package DrevOps\Tui\Handler
 */
class HandlerRegistry {

  /**
   * The registered namespaces, searched in order.
   *
   * @var string[]
   */
  protected array $namespaces = [];

  /**
   * Resolved class names, keyed by field id (NULL means "none found").
   *
   * @var array<string,class-string|null>
   */
  protected array $cache = [];

  /**
   * Construct a registry.
   *
   * @param string[] $namespaces
   *   Namespaces to search, in order.
   */
  public function __construct(array $namespaces = []) {
    foreach ($namespaces as $namespace) {
      $this->addNamespace($namespace);
    }
  }

  /**
   * Register a namespace to search.
   *
   * @param string $namespace
   *   The namespace (with or without surrounding backslashes).
   */
  public function addNamespace(string $namespace): void {
    $this->namespaces[] = trim($namespace, '\\');
    $this->cache = [];
  }

  /**
   * Resolve the consumer class for a field id, or NULL when none exists.
   *
   * @param string $field_id
   *   The field id (e.g. "machine_name").
   *
   * @return class-string|null
   *   The fully qualified class name, or NULL.
   */
  public function resolve(string $field_id): ?string {
    if (array_key_exists($field_id, $this->cache)) {
      return $this->cache[$field_id];
    }

    $class = $this->classNameFor($field_id);
    foreach ($this->namespaces as $namespace) {
      $fqcn = $namespace . '\\' . $class;
      if (class_exists($fqcn)) {
        return $this->cache[$field_id] = $fqcn;
      }
    }

    return $this->cache[$field_id] = NULL;
  }

  /**
   * The reusable validator declared by the field's class, if any.
   *
   * @param string $field_id
   *   The field id.
   *
   * @return \Closure|null
   *   The `fn (mixed $value): ?string` validator, or NULL.
   */
  public function validator(string $field_id): ?\Closure {
    return $this->staticMethod($field_id, 'validate');
  }

  /**
   * The reusable transformer declared by the field's class, if any.
   *
   * @param string $field_id
   *   The field id.
   *
   * @return \Closure|null
   *   The `fn (mixed $value): mixed` transformer, or NULL.
   */
  public function transformer(string $field_id): ?\Closure {
    return $this->staticMethod($field_id, 'transform');
  }

  /**
   * A public static method of the field's class as a closure.
   *
   * @param string $field_id
   *   The field id.
   * @param string $method
   *   The method name.
   *
   * @return \Closure|null
   *   The closure, or NULL when the class or method is absent.
   */
  protected function staticMethod(string $field_id, string $method): ?\Closure {
    $class = $this->resolve($field_id);
    if ($class === NULL || !method_exists($class, $method)) {
      return NULL;
    }

    $reflection = new \ReflectionMethod($class, $method);
    if (!$reflection->isStatic() || !$reflection->isPublic()) {
      return NULL;
    }

    return $reflection->getClosure();
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
