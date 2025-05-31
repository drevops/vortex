<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Stub;

/**
 * Trait MockTrait.
 *
 * This trait provides a method to prepare class mock.
 *
 * @codeCoverageIgnore
 */
trait MockTrait {

  /**
   * Prepare class mock.
   *
   * @param string $class
   *   Class name to generate the mock.
   * @param array<string,mixed> $methods_map
   *   Optional array of methods and values, keyed by method name.
   * @param array<string,mixed>|bool $args
   *   Optional array of constructor arguments. If omitted, a constructor will
   *   not be called. If TRUE, the original constructor will be called as-is.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   An instance of the mock.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.ElseExpression)
   */
  protected function prepareMock(string $class, array $methods_map = [], array|bool $args = []): MockObject {
    $methods = array_filter(array_keys($methods_map));

    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Class %s does not exist', $class));
    }

    $mock = $this->getMockBuilder($class);
    if (is_array($args) && !empty($args)) {
      $mock = $mock->enableOriginalConstructor()->setConstructorArgs($args);
    }
    elseif ($args === FALSE) {
      $mock = $mock->disableOriginalConstructor();
    }
    $mock = $mock->onlyMethods($methods)->getMock();

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
