<?php

namespace DrevOps\Installer\Utils;

class ConstantsLoader {

  public static function load(object|string $class, string|null $prefix = NULL, $prefix_is_key = TRUE): array {
    $reflection = new \ReflectionClass($class);
    $constants = $reflection->getConstants();

    if ($prefix) {
      $constants = array_filter($constants, function ($value, $key) use ($prefix, $prefix_is_key) {
        return str_starts_with($prefix_is_key ? $key : $value, $prefix);
      }, ARRAY_FILTER_USE_BOTH);
    }

    asort($constants);

    return $constants;
  }

}
