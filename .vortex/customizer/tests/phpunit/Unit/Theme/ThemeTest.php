<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Theme;

use DrevOps\Customizer\Theme\DarkTheme;
use DrevOps\Customizer\Theme\LightTheme;
use DrevOps\Customizer\Theme\AbstractTheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the theme base, its concrete themes and the registry.
 */
#[CoversClass(AbstractTheme::class)]
#[CoversClass(DarkTheme::class)]
#[CoversClass(LightTheme::class)]
#[Group('tui')]
final class ThemeTest extends TestCase {

  #[DataProvider('dataProviderCreate')]
  public function testCreate(string $name, string $class, string $role, string $expected): void {
    $theme = AbstractTheme::create($name);

    $this->assertSame($theme::class, $class);
    $this->assertSame($expected, $theme->styleCodes($role));
  }

  public static function dataProviderCreate(): \Iterator {
    yield 'dark' => ['dark', DarkTheme::class, 'value', '32'];
    yield 'light' => ['light', LightTheme::class, 'value', '34'];
    yield 'light indicator' => ['light', LightTheme::class, 'indicator', '35'];
    yield 'default is dark' => ['default', DarkTheme::class, 'title', '1;36'];
    yield 'unknown is dark' => ['bogus', DarkTheme::class, 'title', '1;36'];
  }

  public function testRegister(): void {
    AbstractTheme::register('mylight', LightTheme::class);

    $this->assertInstanceOf(LightTheme::class, AbstractTheme::create('mylight'));
  }

  public function testCreateFromClassName(): void {
    // A theme class name resolves directly, without registration.
    $this->assertInstanceOf(LightTheme::class, AbstractTheme::create(LightTheme::class));
  }

  public function testCustomSubclass(): void {
    $theme = new class() extends AbstractTheme {

      protected function defineStyles(): array {
        return ['title' => '95'];
      }

      protected function defineGlyphs(): array {
        return ['marker' => ['»', '>']];
      }

    };

    $this->assertSame('95', $theme->styleCodes('title'));
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
    $this->assertSame('', $theme->styleCodes('nope'));
  }

  public function testNoColor(): void {
    $theme = new DarkTheme(FALSE);

    $this->assertSame('', $theme->styleCodes('title'));
    $this->assertSame('T', $theme->style('title', 'T'));
    $this->assertFalse($theme->hasColor());
  }

  public function testUnicodeGlyphs(): void {
    $theme = new DarkTheme();

    $this->assertTrue($theme->hasUnicode());
    $this->assertSame('●', $theme->glyph('radio_on'));
    $this->assertSame('◼', $theme->glyph('check_on'));
  }

  public function testAsciiGlyphs(): void {
    $theme = new DarkTheme(TRUE, 76, FALSE);

    $this->assertFalse($theme->hasUnicode());
    $this->assertSame('>', $theme->glyph('marker'));
    $this->assertSame('(*)', $theme->glyph('radio_on'));
    $this->assertSame('[ ]', $theme->glyph('check_off'));
  }

  public function testCreatePassesUnicode(): void {
    $this->assertFalse(AbstractTheme::create('dark', TRUE, 76, FALSE)->hasUnicode());
    $this->assertTrue(AbstractTheme::create('dark')->hasUnicode());
  }

  #[DataProvider('dataProviderDetectUnicode')]
  public function testDetectUnicode(?string $lc_all, ?string $lc_ctype, ?string $lang, bool $expected): void {
    $restore = [];
    foreach (['LC_ALL' => $lc_all, 'LC_CTYPE' => $lc_ctype, 'LANG' => $lang] as $var => $value) {
      $restore[$var] = getenv($var);
      is_string($value) ? putenv($var . '=' . $value) : putenv($var);
    }

    try {
      $this->assertSame($expected, AbstractTheme::detectUnicode());
    }
    finally {
      foreach ($restore as $var => $value) {
        is_string($value) ? putenv($var . '=' . $value) : putenv($var);
      }
    }
  }

  public static function dataProviderDetectUnicode(): \Iterator {
    yield 'utf lang' => [NULL, NULL, 'en_US.UTF-8', TRUE];
    yield 'non-utf lang' => [NULL, NULL, 'C', FALSE];
    yield 'lc_all wins over lang' => ['en_AU.UTF-8', NULL, 'C', TRUE];
    yield 'lc_ctype checked before lang' => [NULL, 'POSIX', 'en_US.UTF-8', FALSE];
    yield 'none set falls back to ascii' => [NULL, NULL, NULL, FALSE];
  }

  #[DataProvider('dataProviderDetectColor')]
  public function testDetectColor(?string $no_color, ?string $term, bool $expected): void {
    $restore = ['NO_COLOR' => getenv('NO_COLOR'), 'TERM' => getenv('TERM')];
    is_string($no_color) ? putenv('NO_COLOR=' . $no_color) : putenv('NO_COLOR');
    is_string($term) ? putenv('TERM=' . $term) : putenv('TERM');

    try {
      $this->assertSame($expected, AbstractTheme::detectColor());
    }
    finally {
      foreach ($restore as $var => $value) {
        is_string($value) ? putenv($var . '=' . $value) : putenv($var);
      }
    }
  }

  public static function dataProviderDetectColor(): \Iterator {
    yield 'normal terminal' => [NULL, 'xterm-256color', TRUE];
    yield 'no_color set' => ['1', 'xterm', FALSE];
    yield 'dumb terminal' => [NULL, 'dumb', FALSE];
    yield 'no_color empty still disables' => ['', 'xterm', FALSE];
  }

}
