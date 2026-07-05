<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Tui\Theme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the theme.
 */
#[CoversClass(Theme::class)]
#[Group('tui')]
final class ThemeTest extends TestCase {

  public function testStyle(): void {
    $theme = new Theme('dark');

    $this->assertSame('1;36', $theme->sgr('title'));
    $this->assertSame("\033[1;36mT\033[0m", $theme->style('title', 'T'));
    $this->assertTrue($theme->hasColor());
  }

  #[DataProvider('dataProviderPresets')]
  public function testPresets(string $preset, string $role, string $expected): void {
    $this->assertSame($expected, (new Theme($preset))->sgr($role));
  }

  public static function dataProviderPresets(): \Iterator {
    yield 'dark value' => ['dark', 'value', '32'];
    yield 'dark title' => ['dark', 'title', '1;36'];
    yield 'light value' => ['light', 'value', '34'];
    yield 'light indicator' => ['light', 'indicator', '35'];
    yield 'default aliases dark' => ['default', 'title', '1;36'];
    yield 'unknown falls back to dark' => ['bogus', 'title', '1;36'];
  }

  public function testStyleOverride(): void {
    $this->assertSame('35', (new Theme('dark', ['styles' => ['value' => '35']]))->sgr('value'));
  }

  public function testGlyphs(): void {
    $theme = new Theme('dark');

    $this->assertSame('❯', $theme->glyph('marker'));
    $this->assertSame('▲', $theme->glyph('indicator_up'));
    $this->assertSame('▼', $theme->glyph('indicator_down'));
    $this->assertSame('', $theme->glyph('nope'));
  }

  public function testGlyphOverride(): void {
    $this->assertSame('>', (new Theme('dark', ['glyphs' => ['marker' => '>']]))->glyph('marker'));
  }

  public function testRegisterCustomPreset(): void {
    Theme::register('brand', ['styles' => ['value' => '95'], 'glyphs' => ['marker' => '»']]);

    $theme = new Theme('brand');

    $this->assertSame('95', $theme->sgr('value'));
    $this->assertSame('»', $theme->glyph('marker'));
    // Omitted tokens fall back to the dark theme.
    $this->assertSame('1;36', $theme->sgr('title'));
    $this->assertSame('▲', $theme->glyph('indicator_up'));
  }

  public function testNoColor(): void {
    $theme = new Theme('dark', [], FALSE);

    $this->assertSame('', $theme->sgr('title'));
    $this->assertSame('T', $theme->style('title', 'T'));
    $this->assertFalse($theme->hasColor());
  }

  public function testUnknownRole(): void {
    $this->assertSame('', (new Theme('dark'))->sgr('nope'));
  }

}
