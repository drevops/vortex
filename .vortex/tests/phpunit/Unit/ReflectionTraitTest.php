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

  #[DataProvider('dataProviderGetProtectedValue')]
  public function testGetProtectedValue(mixed $value): void {
    $object = new ReflectionStub();
    $object->setInstanceValue($value);

    $this->assertSame($value, static::getProtectedValue($object, 'instanceValue'));
  }

  public static function dataProviderGetProtectedValue(): \Iterator {
    yield ['instance value'];
    yield [42];
    yield [0.5];
    yield [TRUE];
    yield [NULL];
    yield [['first', 'second']];
    yield [new \stdClass()];
  }

  public function testGetProtectedValueReadsGivenInstance(): void {
    $first = new ReflectionStub();
    $first->setInstanceValue('first value');

    $second = new ReflectionStub();
    $second->setInstanceValue('second value');

    $this->assertSame('first value', static::getProtectedValue($first, 'instanceValue'));
    $this->assertSame('second value', static::getProtectedValue($second, 'instanceValue'));
  }

  public function testGetProtectedValueStaticProperty(): void {
    $object = new ReflectionStub();

    $this->assertSame('static value', static::getProtectedValue($object, 'staticValue'));
  }

  public function testGetProtectedValueInheritedProperty(): void {
    $object = new ReflectionChildStub();
    $object->setInstanceValue('inherited value');

    $this->assertSame('inherited value', static::getProtectedValue($object, 'instanceValue'));
    $this->assertSame('child value', static::getProtectedValue($object, 'childValue'));
  }

  public function testGetProtectedValueMissingProperty(): void {
    $object = new ReflectionStub();

    $this->expectException(\ReflectionException::class);
    $this->expectExceptionMessage('does not exist');

    static::getProtectedValue($object, 'missingValue');
  }

  public function testSetProtectedValue(): void {
    $object = new ReflectionStub();

    static::setProtectedValue($object, 'instanceValue', 'assigned value');

    $this->assertSame('assigned value', static::getProtectedValue($object, 'instanceValue'));
  }

  public function testSetProtectedValueInheritedProperty(): void {
    $object = new ReflectionChildStub();

    static::setProtectedValue($object, 'instanceValue', 'assigned to parent');

    $this->assertSame('assigned to parent', static::getProtectedValue($object, 'instanceValue'));
  }

  public function testCallProtectedMethod(): void {
    $object = new ReflectionStub();

    $this->assertSame('instance: first, second', static::callProtectedMethod($object, 'concatenate', ['first', 'second']));
  }

  public function testCallProtectedMethodStatic(): void {
    $object = new ReflectionStub();

    $this->assertSame('static: value', static::callProtectedMethod($object, 'prefix', ['value']));
    $this->assertSame('static: value', static::callProtectedMethod(ReflectionStub::class, 'prefix', ['value']));
  }

  public function testCallProtectedMethodMissingClass(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Class NoSuchClass does not exist');

    static::callProtectedMethod('NoSuchClass', 'prefix');
  }

  public function testCallProtectedMethodMissingMethod(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Method missingMethod does not exist');

    static::callProtectedMethod(new ReflectionStub(), 'missingMethod');
  }

  public function testCallProtectedMethodWithoutInstance(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('An object instance is required for non-static methods');

    static::callProtectedMethod(ReflectionStub::class, 'concatenate', ['first', 'second']);
  }

}

class ReflectionStub {

  /**
   * Value shared by every instance.
   */
  protected static string $staticValue = 'static value';

  /**
   * Value held by a single instance.
   */
  protected mixed $instanceValue = 'instance value';

  public function setInstanceValue(mixed $value): void {
    $this->instanceValue = $value;
  }

  protected function concatenate(string $first, string $second): string {
    return sprintf('instance: %s, %s', $first, $second);
  }

  protected static function prefix(string $value): string {
    return sprintf('static: %s', $value);
  }

}

class ReflectionChildStub extends ReflectionStub {

  /**
   * Value declared on the child class.
   */
  protected string $childValue = 'child value';

}
