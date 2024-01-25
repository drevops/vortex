<?php

namespace Drevops\Installer\Tests\Unit\Bag;

use DrevOps\Installer\Bag\Answers;
use Drevops\Installer\Tests\Unit\UnitTestBase;

/**
 * @coversDefaultClass \Drevops\Installer\Bag\Answers
 * @runTestsInSeparateProcesses
 */
class AnswersTest extends UnitTestBase {

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot instantiate Singleton class directly. Use ::getInstance() instead.');
    (new Answers());
  }

  /**
   * @covers ::getInstance
   */
  public function AnswersInstance(): void {
    $first = Answers::getInstance();
    $second = Answers::getInstance();

    $this->assertSame($first, $second, 'Both instances should be the same');
  }

  /**
   * @covers ::__clone
   */
  public function testCloneIsDisabled(): void {
    Answers::getInstance();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cloning of Singleton is disallowed.');
  }

  /**
   * @covers ::__wakeup
   */
  public function testUnserializeIsDisabled(): void {
    Answers::getInstance();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unserializing instances of Singleton classes is disallowed.');
  }

  /**
   * @covers ::get
   * @covers ::set
   */
  public function testSetAndGet(): void {
    $bag = Answers::getInstance();

    $bag->set('testKey', 'testValue');
    $this->assertEquals('testValue', $bag->get('testKey'));
    $this->assertEquals('default', $bag->get('nonExistentKey', 'default'));
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll(): void {
    $bag = Answers::getInstance();

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
    $bag = Answers::getInstance();
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
    $bag = Answers::getInstance();

    $bag->set('someKey', 'someValue');
    $this->assertEquals('someValue', $bag->get('someKey'));

    $bag->clear();
    $this->assertEquals([], $bag->getAll());
  }

}
