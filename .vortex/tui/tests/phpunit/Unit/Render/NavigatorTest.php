<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Render;

use DrevOps\Tui\Config\Panel;
use DrevOps\Tui\Render\Navigator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the panel navigator.
 */
#[CoversClass(Navigator::class)]
#[Group('tui')]
final class NavigatorTest extends TestCase {

  public function testNavigation(): void {
    $sub = new Panel('adv', 'Advanced', '');
    $navigator = new Navigator(new Panel('hub', 'Hub', '', [], [$sub]));

    $this->assertSame('Hub', $navigator->current()->title);
    $this->assertTrue($navigator->isRoot());
    $this->assertSame(1, $navigator->depth());
    $this->assertSame(['Hub'], $navigator->breadcrumb());

    $navigator->enter($sub);
    $this->assertSame('Advanced', $navigator->current()->title);
    $this->assertFalse($navigator->isRoot());
    $this->assertSame(2, $navigator->depth());
    $this->assertSame(['Hub', 'Advanced'], $navigator->breadcrumb());

    $this->assertTrue($navigator->pop());
    $this->assertSame('Hub', $navigator->current()->title);
    $this->assertFalse($navigator->pop());
  }

}
