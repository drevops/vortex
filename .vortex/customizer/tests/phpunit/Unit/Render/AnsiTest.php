<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Render;

use DrevOps\Tui\Render\Ansi;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ANSI helpers.
 */
#[CoversClass(Ansi::class)]
#[Group('tui')]
final class AnsiTest extends TestCase {

  public function testStyle(): void {
    $this->assertSame("\033[1;32mhi\033[0m", Ansi::style('hi', '1;32'));
    $this->assertSame('hi', Ansi::style('hi', ''));
  }

  public function testStripAndWidth(): void {
    $styled = Ansi::style('hello', '32');

    $this->assertSame('hello', Ansi::strip($styled));
    $this->assertSame(5, Ansi::width($styled));
    $this->assertSame(3, Ansi::width('❯ x'));
  }

  public function testAlignRight(): void {
    $this->assertSame('ab   Z', Ansi::alignRight('ab', 'Z', 6));

    $styled = Ansi::alignRight('ab', Ansi::style('Z', '7'), 6);
    $this->assertSame(6, Ansi::width($styled));
  }

  public function testAlignRightMinimumPad(): void {
    $this->assertSame('abcdef X', Ansi::alignRight('abcdef', 'X', 3));
  }

}
