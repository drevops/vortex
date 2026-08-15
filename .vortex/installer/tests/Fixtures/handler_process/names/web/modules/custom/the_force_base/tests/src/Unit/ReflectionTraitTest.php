<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class ReflectionTraitTest.
 *
 * Tests for the ReflectionTrait.
 *
 * @package Drupal\the_force_base\Tests
 */
#[Group('TheForceBase')]
class ReflectionTraitTest extends TheForceBaseUnitTestBase {

  /**
   * Tests reading a protected instance property.
   */
  #[DataProvider('dataProviderGetProtectedValue')]
  public function testGetProtectedValue(mixed $value): void {
    $object = new ReflectionTraitTestStub();
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
    $first = new ReflectionTraitTestStub();
    $first->setInstanceValue('first value');

    $second = new ReflectionTraitTestStub();
    $second->setInstanceValue('second value');

    $this->assertSame('first value', static::getProtectedValue($first, 'instanceValue'));
    $this->assertSame('second value', static::getProtectedValue($second, 'instanceValue'));
  }

  /**
   * Tests reading a protected static property.
   */
  public function testGetProtectedValueStaticProperty(): void {
    $object = new ReflectionTraitTestStub();

    $this->assertSame('static value', static::getProtectedValue($object, 'staticValue'));
  }

  /**
   * Tests reading a protected property declared on a parent class.
   */
  public function testGetProtectedValueInheritedProperty(): void {
    $object = new ReflectionTraitTestChildStub();
    $object->setInstanceValue('inherited value');

    $this->assertSame('inherited value', static::getProtectedValue($object, 'instanceValue'));
    $this->assertSame('child value', static::getProtectedValue($object, 'childValue'));
  }

  /**
   * Tests reading a property that the object does not declare.
   */
  public function testGetProtectedValueMissingProperty(): void {
    $object = new ReflectionTraitTestStub();

    $this->expectException(\ReflectionException::class);
    $this->expectExceptionMessage('does not exist');

    static::getProtectedValue($object, 'missingValue');
  }

}

/**
 * Class ReflectionTraitTestStub.
 *
 * Stub with protected properties to read through reflection.
 *
 * @package Drupal\the_force_base\Tests
 */
class ReflectionTraitTestStub {

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

}

/**
 * Class ReflectionTraitTestChildStub.
 *
 * Stub inheriting the protected properties of its parent.
 *
 * @package Drupal\the_force_base\Tests
 */
class ReflectionTraitTestChildStub extends ReflectionTraitTestStub {

  /**
   * Value declared on the child class.
   */
  protected string $childValue = 'child value';

}
