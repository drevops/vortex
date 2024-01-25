<?php

namespace DrevOps\Installer\Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use DrevOps\Installer\Trait\ReadOnlyTrait;

/**
 * @coversDefaultClass \DrevOps\Installer\Trait\ReadOnlyTrait
 */
class ReadOnlyTraitTest extends TestCase {

  private object $subject;

  protected function setUp(): void {
    $this->subject = new class {
      use ReadOnlyTrait;
    };
  }

  /**
   * @covers ::isReadOnly
   */
  public function testIsInitiallyNotReadOnly(): void {
    $this->assertFalse($this->subject->isReadOnly());
  }

  /**
   * @covers ::setReadOnly
   * @covers ::isReadOnly
   */
  public function testCanBeMadeReadOnly(): void {
    $this->subject->setReadOnly();

    $this->assertTrue($this->subject->isReadOnly());
  }

}
