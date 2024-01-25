<?php

namespace Drevops\Installer\Tests\Unit\Bag;

use DrevOps\Installer\Bag\AbstractBag;
use Drevops\Installer\Tests\Unit\UnitTestCase;

/**
 * @coversDefaultClass \Drevops\Installer\Bag\AbstractBag
 */
class AbstractBagTest extends UnitTestCase {

  /**
   * @covers ::get
   * @covers ::set
   */
  public function testSetAndGet(): void {
    $bag = new TestBag();

    $bag->set('testKey', 'testValue');
    $this->assertEquals('testValue', $bag->get('testKey'));
    $this->assertEquals('default', $bag->get('nonExistentKey', 'default'));
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll(): void {
    $bag = new TestBag();

    $bag->set('key1', 'value1');
    $bag->set('key2', 'value2');

    $this->assertEquals([
      'key1' => 'value1',
      'key2' => 'value2',
    ], $bag->getAll());
  }

  /**
   * @covers ::fromValues
   */
  public function testFromValues(): void {
    $bag = new TestBag();
    $bag->fromValues([
      'keyA' => 'valueA',
      'keyB' => 'valueB',
    ]);

    $this->assertEquals('valueA', $bag->get('keyA'));
    $this->assertEquals('valueB', $bag->get('keyB'));
  }

  /**
   * @covers ::clear
   */
  public function testClear(): void {
    $bag = new TestBag();

    $bag->set('someKey', 'someValue');
    $this->assertEquals('someValue', $bag->get('someKey'));

    $bag->clear();
    $this->assertEquals([], $bag->getAll());
  }

}

/**
 *
 */
class TestBag extends AbstractBag {

}
