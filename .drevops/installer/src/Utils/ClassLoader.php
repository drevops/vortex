<?php

namespace DrevOps\Installer\Utils;

/**
 * Class loader.
 */
class ClassLoader {

  /**
   * Load classes from path.
   *
   * @param string $path
   *   Parent class name.
   * @param string $parent_class
   *   Lookup path.
   *
   * @return array
   *   Array of loaded class instances.
   */
  public static function load(string $path, $parent_class = NULL): array {
    if (!empty($path) && is_dir($path)) {
      foreach (glob($path . '/*.php') as $filename) {
        if ($filename !== __FILE__ && !str_contains(basename($filename), 'Trait')) {
          require_once $filename;
        }
      }
    }

    $classes = get_declared_classes();

    return $parent_class ? static::filterByClass($parent_class, $classes) : $classes;
  }

  /**
   * Filter classes by the parent class.
   *
   * Classes should already be loaded before calling this method.
   *
   * To load classes using Composer's autoloader, specify them in the
   * composer.json file as follows:
   *
   * @code
   * {
   *   "autoload": {
   *     "files": [
   *       "src/path/to/MyClass.php"
   *     ]
   *   },
   * }
   * @endcode
   *
   * @param string $parent_class
   *   Parent class name.
   * @param array $classes
   *   Lookup path.
   *
   * @return array
   *   Array of loaded class instances.
   */
  public static function filterByClass($parent_class, $classes = NULL): array {
    $classes = $classes ?? get_declared_classes();

    foreach ($classes as $k => $class) {
      if (!is_subclass_of($class, $parent_class) || (new \ReflectionClass($class))->isAbstract()) {
        unset($classes[$k]);
      }
    }

    return $classes;
  }

}
