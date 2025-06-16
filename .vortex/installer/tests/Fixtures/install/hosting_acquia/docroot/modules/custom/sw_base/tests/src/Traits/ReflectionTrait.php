<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_base\Traits;

/**
 * Trait ReflectionTrait.
 *
 * Provides methods to work with class reflection.
 *
 * @codeCoverageIgnore
 */
trait ReflectionTrait {

  /**
   * Call protected methods on the class.
   *
   * @param object|string $object
   *   Object or class name to use for a method call.
   * @param string $name
   *   Method name. Method can be static.
   * @param array $args
   *   Array of arguments to pass to the method. To pass arguments by reference,
   *   pass them by reference as an element of this array.
   *
   * @return mixed
   *   Method result.
   */
  protected static function callProtectedMethod(object|string $object, string $name, array $args = []) {
    $object_or_class = is_object($object) ? $object::class : $object;

    if (!class_exists($object_or_class)) {
      throw new \InvalidArgumentException(sprintf('Class %s does not exist', $object_or_class));
    }

    $class = new \ReflectionClass($object_or_class);

    if (!$class->hasMethod($name)) {
      throw new \InvalidArgumentException(sprintf('Method %s does not exist', $name));
    }

    $method = $class->getMethod($name);

    $original_accessibility = $method->isPublic();

    // Set method accessibility to true, so it can be invoked.
    $method->setAccessible(TRUE);

    // If the method is static, we won't pass an object instance to invokeArgs()
    // Otherwise, we ensure to pass the object instance.
    $invoke_object = $method->isStatic() ? NULL : (is_object($object) ? $object : NULL);

    // Ensure we have an object for non-static methods.
    if (!$method->isStatic() && $invoke_object === NULL) {
      throw new \InvalidArgumentException("An object instance is required for non-static methods");
    }

    $result = $method->invokeArgs($invoke_object, $args);

    // Reset the method's accessibility to its original state.
    $method->setAccessible($original_accessibility);

    return $result;
  }

  /**
   * Set protected property value.
   */
  protected static function setProtectedValue(object $object, string $property, mixed $value): void {
    $class = new \ReflectionClass($object::class);
    $property = $class->getProperty($property);
    $property->setAccessible(TRUE);

    $property->setValue($object, $value);
  }

  /**
   * Get protected value from the object.
   *
   * @param object $object
   *   Object to set the value on.
   * @param string $property
   *   Property name to get the value. Property should exists in the object.
   *
   * @return mixed
   *   Protected property value.
   */
  protected static function getProtectedValue($object, $property): mixed {
    $class = new \ReflectionClass($object::class);
    $property = $class->getProperty($property);
    $property->setAccessible(TRUE);

    return $property->getValue($class);
  }

}
