<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Tui\Scroller;
use DrevOps\Customizer\Tui\Viewport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the scroller and viewport.
 */
#[CoversClass(Scroller::class)]
#[CoversClass(Viewport::class)]
#[Group('tui')]
final class ScrollerTest extends TestCase {

  public function testCursorAtTop(): void {
    $viewport = (new Scroller())->compute(10, 4, 0, 0);

    $this->assertSame(0, $viewport->offset);
    $this->assertFalse($viewport->has_above);
    $this->assertTrue($viewport->has_below);
  }

  public function testFollowsCursorDown(): void {
    $viewport = (new Scroller())->compute(10, 4, 6, 0);

    $this->assertSame(3, $viewport->offset);
    $this->assertTrue($viewport->has_above);
    $this->assertTrue($viewport->has_below);
  }

  public function testFollowsCursorUp(): void {
    $this->assertSame(2, (new Scroller())->compute(10, 4, 2, 5)->offset);
  }

  public function testCursorAtBottom(): void {
    $viewport = (new Scroller())->compute(10, 4, 9, 0);

    $this->assertSame(6, $viewport->offset);
    $this->assertTrue($viewport->has_above);
    $this->assertFalse($viewport->has_below);
  }

  public function testEmptyOrZeroHeight(): void {
    $this->assertFalse((new Scroller())->compute(0, 4, 0, 0)->has_below);
    $this->assertFalse((new Scroller())->compute(10, 0, 0, 0)->has_below);
  }

  public function testScrollClamps(): void {
    $scroller = new Scroller();

    $this->assertSame(3, $scroller->scroll(2, 1, 10, 4));
    $this->assertSame(6, $scroller->scroll(5, 5, 10, 4));
    $this->assertSame(0, $scroller->scroll(0, -5, 10, 4));
  }

  public function testSlice(): void {
    $this->assertSame(['c', 'd'], (new Scroller())->slice(['a', 'b', 'c', 'd', 'e'], 2, 2));
  }

}
