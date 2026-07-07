<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Tui\Terminal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the terminal output.
 */
#[CoversClass(Terminal::class)]
#[Group('tui')]
final class TerminalTest extends TestCase {

  public function testWriteAndRender(): void {
    $stream = fopen('php://memory', 'rw');
    $this->assertIsResource($stream);

    $terminal = new Terminal($stream);
    $terminal->write('hello');
    $terminal->render('FRAME');

    rewind($stream);
    $contents = (string) stream_get_contents($stream);
    fclose($stream);

    $this->assertStringContainsString('hello', $contents);
    $this->assertStringContainsString('FRAME', $contents);
    $this->assertStringContainsString("\033[2J", $contents);
  }

  public function testHeight(): void {
    $this->assertGreaterThan(0, (new Terminal())->height());
  }

  public function testClear(): void {
    $stream = fopen('php://memory', 'rw');
    $this->assertIsResource($stream);

    $terminal = new Terminal($stream);
    $terminal->clear();

    rewind($stream);
    $contents = (string) stream_get_contents($stream);
    fclose($stream);

    $this->assertStringContainsString("\033[2J", $contents);
  }

}
