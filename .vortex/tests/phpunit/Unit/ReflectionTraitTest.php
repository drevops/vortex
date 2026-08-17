<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Unit;

use Drupal\Tests\ys_base\Traits\ReflectionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ReflectionTrait shipped to consumer sites.
 */
class ReflectionTraitTest extends TestCase {

  use ReflectionTrait;

  /**
   * Tests reading a protected instance property.
   */
  #[DataProvider('dataProviderGetProtectedValue')]
  public function testGetProtectedValue(mixed $value): void {
    $object = new ReflectionStub();
    $object->setInstanceValue($value);

    $this->assertSame($value, static::getProtectedValue($object, 'instanceValue'));
  }

  /**
   * Data provider for testGetProtectedValue().
   */
  public static function dataProviderGetProtectedValue(): \Iterator {
    yield ['instance value'];
    yield [42];
    yield [0.5];
    yield [TRUE];
    yield [NULL];
    yield [['first', 'second']];
    yield [new \stdClass()];
  }

  /**
   * Tests that the value is read from the given instance.
   */
  public function testGetProtectedValueReadsGivenInstance(): void {
    $first = new ReflectionStub();
    $first->setInstanceValue('first value');

    $second = new ReflectionStub();
    $second->setInstanceValue('second value');

    $this->assertSame('first value', static::getProtectedValue($first, 'instanceValue'));
    $this->assertSame('second value', static::getProtectedValue($second, 'instanceValue'));
  }

  /**
   * Tests reading a protected static property.
   */
  public function testGetProtectedValueStaticProperty(): void {
    $object = new ReflectionStub();

    $this->assertSame('static value', static::getProtectedValue($object, 'staticValue'));
  }

  /**
   * Tests reading a protected property declared on a parent class.
   */
  public function testGetProtectedValueInheritedProperty(): void {
    $object = new ReflectionChildStub();
    $object->setInstanceValue('inherited value');

    $this->assertSame('inherited value', static::getProtectedValue($object, 'instanceValue'));
    $this->assertSame('child value', static::getProtectedValue($object, 'childValue'));
  }

  /**
   * Tests reading a property that the object does not declare.
   */
  public function testGetProtectedValueMissingProperty(): void {
    $object = new ReflectionStub();

    $this->expectException(\ReflectionException::class);
    $this->expectExceptionMessage('does not exist');

    static::getProtectedValue($object, 'missingValue');
  }

  /**
   * Tests writing a protected instance property.
   */
  public function testSetProtectedValue(): void {
    $object = new ReflectionStub();

    static::setProtectedValue($object, 'instanceValue', 'assigned value');

    $this->assertSame('assigned value', static::getProtectedValue($object, 'instanceValue'));
  }

  /**
   * Tests writing a protected property declared on a parent class.
   */
  public function testSetProtectedValueInheritedProperty(): void {
    $object = new ReflectionChildStub();

    static::setProtectedValue($object, 'instanceValue', 'assigned to parent');

    $this->assertSame('assigned to parent', static::getProtectedValue($object, 'instanceValue'));
  }

  /**
   * Tests calling a protected instance method.
   */
  public function testCallProtectedMethod(): void {
    $object = new ReflectionStub();

    $this->assertSame('instance: first, second', static::callProtectedMethod($object, 'concatenate', ['first', 'second']));
  }

  /**
   * Tests calling a protected static method on an object and on a class name.
   */
  public function testCallProtectedMethodStatic(): void {
    $object = new ReflectionStub();

    $this->assertSame('static: value', static::callProtectedMethod($object, 'prefix', ['value']));
    $this->assertSame('static: value', static::callProtectedMethod(ReflectionStub::class, 'prefix', ['value']));
  }

  /**
   * Tests calling a protected method on a class that does not exist.
   */
  public function testCallProtectedMethodMissingClass(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Class NoSuchClass does not exist');

    static::callProtectedMethod('NoSuchClass', 'prefix');
  }

  /**
   * Tests calling a protected method that the class does not declare.
   */
  public function testCallProtectedMethodMissingMethod(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Method missingMethod does not exist');

    static::callProtectedMethod(new ReflectionStub(), 'missingMethod');
  }

  /**
   * Tests calling a non-static protected method without an instance.
   */
  public function testCallProtectedMethodWithoutInstance(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('An object instance is required for non-static methods');

    static::callProtectedMethod(ReflectionStub::class, 'concatenate', ['first', 'second']);
  }

}

/**
 * Stub with protected members to reach through reflection.
 */
class ReflectionStub {

  /**
   * Value shared by every instance.
   */
  protected static string $staticValue = 'static value';

  /**
   * Value held by a single instance.
   */
  protected mixed $instanceValue = 'instance value';

  /**
   * Assigns the protected instance property.
   */
  public function setInstanceValue(mixed $value): void {
    $this->instanceValue = $value;
  }

  /**
   * Joins the given arguments.
   */
  protected function concatenate(string $first, string $second): string {
    return sprintf('instance: %s, %s', $first, $second);
  }

  /**
   * Prefixes the given argument.
   */
  protected static function prefix(string $value): string {
    return sprintf('static: %s', $value);
  }

}

/**
 * Stub inheriting the protected members of its parent.
 */
class ReflectionChildStub extends ReflectionStub {

  /**
   * Value declared on the child class.
   */
  protected string $childValue = 'child value';

}
