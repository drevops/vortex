<?php

namespace Drevops\Installer\Tests\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Stub;

/**
 * Trait MockTrait.
 *
 * This trait provides a method to prepare class mock.
 */
trait MockTrait {

  /**
   * Prepare class mock.
   *
   * @param string $class
   *   Class name to generate the mock.
   * @param array<non-empty-string,mixed> $methods_map
   *   Optional array of methods and values, keyed by method name.
   * @param array<string,mixed>|bool $args
   *   Optional array of constructor arguments. If omitted, a constructor will
   *   not be called. If TRUE, the original constructor will be called as-is.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   An instance of the mock.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function prepareMock(string $class, array $methods_map = [], array|bool $args = []): MockObject {
    $methods = array_filter(array_keys($methods_map), 'is_string');

    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Class %s does not exist', $class));
    }

    $reflection_class = new \ReflectionClass($class);

    if ($reflection_class->isAbstract()) {
      $mock = $this->getMockForAbstractClass($class, is_array($args) ? $args : [], '', !empty($args), TRUE, TRUE, $methods);
    }
    else {
      $mock = $this->getMockBuilder($class);
      if (is_array($args) && !empty($args)) {
        $mock = $mock->enableOriginalConstructor()
          ->setConstructorArgs($args);
      }
      elseif ($args === FALSE) {
        $mock = $mock->disableOriginalConstructor();
      }

      if (!empty($methods)) {
        $mock = $mock->onlyMethods($methods);
      }

      $mock = $mock->getMock();
    }

    foreach ($methods_map as $method => $value) {
      // Handle callback values differently.
      if ($value instanceof Stub) {
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

}
