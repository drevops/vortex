<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Render;

use DrevOps\Tui\Render\TerminalControl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the terminal control sequences.
 */
#[CoversClass(TerminalControl::class)]
#[Group('tui')]
final class TerminalControlTest extends TestCase {

  public function testSequences(): void {
    $this->assertSame("\033[?1049h", TerminalControl::altScreenOn());
    $this->assertSame("\033[?1049l", TerminalControl::altScreenOff());
    $this->assertSame("\033[?25l", TerminalControl::hideCursor());
    $this->assertSame("\033[?25h", TerminalControl::showCursor());
    $this->assertSame("\033[?1000h\033[?1006h", TerminalControl::mouseOn());
    $this->assertSame("\033[?1000l\033[?1006l", TerminalControl::mouseOff());
    $this->assertSame("\033[2J\033[H", TerminalControl::clear());
  }

  public function testRestoreCombines(): void {
    $this->assertSame("\033[?1000l\033[?1006l\033[?25h\033[?1049l", TerminalControl::restore());
  }

}
