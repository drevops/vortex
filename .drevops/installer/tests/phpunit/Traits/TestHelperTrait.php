<?php

namespace Drevops\Installer\Tests\Traits;

/**
 * Trait DrevopsTestHelperTrait.
 *
 * DrevopsTestHelperTrait fixture class.
 *
 * @package Drevops\Tests
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
trait TestHelperTrait {

  /**
   * Call protected methods on the class.
   *
   * @param object|string $object
   *   Object or class name to use for a method call.
   * @param string $method
   *   Method name. Method can be static.
   * @param array $args
   *   Array of arguments to pass to the method. To pass arguments by reference,
   *   pass them by reference as an element of this array.
   *
   * @return mixed
   *   Method result.
   */
  protected static function callProtectedMethod($object, $methodName, array $args = []) {
    // Create a reflection of the class or object
    $class = new \ReflectionClass(is_object($object) ? get_class($object) : $object);

    // Check if the method exists, if not, throw a clear exception
    if (!$class->hasMethod($methodName)) {
      throw new \InvalidArgumentException("Method $methodName does not exist");
    }

    // Get a reflection of the method
    $method = $class->getMethod($methodName);

    // Store the current accessibility state
    $originalAccessibility = $method->isPublic();

    // Set method accessibility to true, so it can be invoked
    $method->setAccessible(TRUE);

    // If the method is static, we won't pass an object instance to invokeArgs()
    // Otherwise, we ensure to pass the object instance
    $invokeObject = $method->isStatic() ? NULL : (is_object($object) ? $object : NULL);

    // Ensure we have an object for non-static methods
    if (!$method->isStatic() && $invokeObject === NULL) {
      throw new \InvalidArgumentException("An object instance is required for non-static methods");
    }

    // Call the method
    $result = $method->invokeArgs($invokeObject, $args);

    // Reset the method's accessibility to its original state
    $method->setAccessible($originalAccessibility);

    return $result;
  }

  /**
   * Set protected property value.
   *
   * @param object $object
   *   Object to set the value on.
   * @param string $property
   *   Property name to set the value. Property should exists in the object.
   * @param mixed $value
   *   Value to set to the property.
   */
  protected static function setProtectedValue($object, $property, $value) {
    $class = new \ReflectionClass(get_class($object));
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
  protected static function getProtectedValue($object, $property) {
    $class = new \ReflectionClass(get_class($object));
    $property = $class->getProperty($property);
    $property->setAccessible(TRUE);

    return $property->getValue($class);
  }

  /**
   * Helper to prepare class mock.
   *
   * @param string $class
   *   Class name to generate the mock.
   * @param array $methodsMap
   *   Optional array of methods and values, keyed by method name.
   * @param array $args
   *   Optional array of constructor arguments. If omitted, a constructor will
   *   not be called.
   *
   * @return object
   *   Mocked class.
   */
  protected function prepareMock($class, array $methodsMap = [], array $args = []) {
    $methods = array_keys($methodsMap);

    $reflectionClass = new \ReflectionClass($class);

    if ($reflectionClass->isAbstract()) {
      $mock = $this->getMockForAbstractClass(
        $class, $args, '', !empty($args), TRUE, TRUE, $methods
      );
    }
    else {
      $mock = $this->getMockBuilder($class);
      if (!empty($args)) {
        $mock = $mock->enableOriginalConstructor()
          ->setConstructorArgs($args);
      }
      else {
        $mock = $mock->disableOriginalConstructor();
      }
      $mock = $mock->onlyMethods($methods)
        ->getMock();
    }

    foreach ($methodsMap as $method => $value) {
      // Handle callback values differently.
      if (is_object($value) && !str_contains(get_class($value), 'Callback') && !str_contains(get_class($value), 'Closure')) {
        $mock->expects($this->any())
          ->method($method)
          ->will($value);
      }
      elseif (is_callable($value)) {
        $mock->expects($this->any())
          ->method($method)
          ->willReturnCallback($value);
      }
      else {
        $mock->expects($this->any())
          ->method($method)
          ->willReturn($value);
      }
    }

    return $mock;
  }

  /**
   * Check if testing framework was ran with --debug option.
   */
  protected function isDebug() {
    return in_array('--debug', $_SERVER['argv'], TRUE);
  }

}
