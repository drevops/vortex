<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Tui\Theme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the theme.
 */
#[CoversClass(Theme::class)]
#[Group('tui')]
final class ThemeTest extends TestCase {

  public function testPresetAndStyle(): void {
    $theme = new Theme('default');

    $this->assertSame('1;36', $theme->sgr('title'));
    $this->assertSame("\033[1;36mT\033[0m", $theme->style('title', 'T'));
    $this->assertTrue($theme->hasColor());
  }

  public function testGreenPreset(): void {
    $this->assertSame('1;32', (new Theme('green'))->sgr('value'));
  }

  public function testOverride(): void {
    $this->assertSame('35', (new Theme('default', ['value' => '35']))->sgr('value'));
  }

  public function testNoColor(): void {
    $theme = new Theme('default', [], FALSE);

    $this->assertSame('', $theme->sgr('title'));
    $this->assertSame('T', $theme->style('title', 'T'));
    $this->assertFalse($theme->hasColor());
  }

  public function testUnknownPresetFallsBack(): void {
    $this->assertSame('1;36', (new Theme('bogus'))->sgr('title'));
  }

  public function testUnknownRole(): void {
    $this->assertSame('', (new Theme('default'))->sgr('nope'));
  }

}
