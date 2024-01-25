<?php

namespace DrevOps\Installer\Tests\Unit\Traits;

use Drevops\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Trait\SingletonTrait;

/**
 * @coversDefaultClass \DrevOps\Installer\Trait\SingletonTrait
 * @runClassInSeparateProcess
 */
class SingletonTraitTest extends UnitTestBase {

  /**
   * @covers ::__construct
   * @convertErrorsToExceptions
   */
  public function testConstructor(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot instantiate Singleton class directly. Use ::getInstance() instead.');
    (new TestSingleton());
  }

  /**
   * @covers ::getInstance
   */
  public function testSingletonInstance(): void {
    $first = TestSingleton::getInstance();
    $second = TestSingleton::getInstance();

    $this->assertSame($first, $second, 'Both instances should be the same');
  }

  /**
   * @covers ::__clone
   */
  public function testCloneIsDisabled(): void {
    TestSingleton::getInstance();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cloning of Singleton is disallowed.');
  }

  /**
   * @covers ::__wakeup
   */
  public function testUnserializeIsDisabled(): void {
    TestSingleton::getInstance();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unserializing instances of Singleton classes is disallowed.');
  }

}

class TestSingleton {

  use SingletonTrait;

}
