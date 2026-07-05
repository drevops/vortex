<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Tui\DarkTheme;
use DrevOps\Customizer\Tui\LightTheme;
use DrevOps\Customizer\Tui\Theme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the theme base, its concrete themes and the registry.
 */
#[CoversClass(Theme::class)]
#[CoversClass(DarkTheme::class)]
#[CoversClass(LightTheme::class)]
#[Group('tui')]
final class ThemeTest extends TestCase {

  #[DataProvider('dataProviderCreate')]
  public function testCreate(string $name, string $class, string $role, string $expected): void {
    $theme = Theme::create($name);

    $this->assertSame($theme::class, $class);
    $this->assertSame($expected, $theme->sgr($role));
  }

  public static function dataProviderCreate(): \Iterator {
    yield 'dark' => ['dark', DarkTheme::class, 'value', '32'];
    yield 'light' => ['light', LightTheme::class, 'value', '34'];
    yield 'light indicator' => ['light', LightTheme::class, 'indicator', '35'];
    yield 'default is dark' => ['default', DarkTheme::class, 'title', '1;36'];
    yield 'unknown is dark' => ['bogus', DarkTheme::class, 'title', '1;36'];
  }

  public function testRegister(): void {
    Theme::register('mylight', LightTheme::class);

    $this->assertInstanceOf(LightTheme::class, Theme::create('mylight'));
  }

  public function testCreateFromClassName(): void {
    // A theme class name resolves directly, without registration.
    $this->assertInstanceOf(LightTheme::class, Theme::create(LightTheme::class));
  }

  public function testCustomSubclass(): void {
    $theme = new class() extends Theme {

      protected function defineStyles(): array {
        return ['title' => '95'];
      }

      protected function defineGlyphs(): array {
        return ['marker' => '»'];
      }

    };

    $this->assertSame('95', $theme->sgr('title'));
    $this->assertSame('»', $theme->glyph('marker'));
  }

  public function testGlyphs(): void {
    $theme = new DarkTheme();

    $this->assertSame('❯', $theme->glyph('marker'));
    $this->assertSame('▲', $theme->glyph('indicator_up'));
    $this->assertSame('', $theme->glyph('nope'));
  }

  public function testStyleAndColor(): void {
    $theme = new DarkTheme();

    $this->assertSame("\033[1;36mT\033[0m", $theme->style('title', 'T'));
    $this->assertTrue($theme->hasColor());
    $this->assertSame('', $theme->sgr('nope'));
  }

  public function testNoColor(): void {
    $theme = new DarkTheme(FALSE);

    $this->assertSame('', $theme->sgr('title'));
    $this->assertSame('T', $theme->style('title', 'T'));
    $this->assertFalse($theme->hasColor());
  }

}
