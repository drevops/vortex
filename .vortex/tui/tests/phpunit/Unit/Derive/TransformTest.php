<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Derive;

use DrevOps\Tui\Derive\Transform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the value transforms.
 */
#[CoversClass(Transform::class)]
#[Group('derive')]
final class TransformTest extends TestCase {

  #[DataProvider('dataProviderApply')]
  public function testApply(string $name, string $input, string $expected): void {
    $this->assertSame($expected, Transform::apply($input, $name));
  }

  public static function dataProviderApply(): \Iterator {
    // Inherited str2name conversions.
    yield 'machine' => ['machine', 'My Site! 2', 'my_site_2'];
    yield 'kebab' => ['kebab', 'My Site', 'my-site'];
    yield 'pascal' => ['pascal', 'my_site', 'MySite'];
    yield 'snake' => ['snake', 'My Site', 'my_site'];
    // TUI-only transforms.
    yield 'lower' => ['lower', 'HeLLo', 'hello'];
    yield 'upper' => ['upper', 'HeLLo', 'HELLO'];
    yield 'host' => ['host', 'My_Site.Com', 'my-site.com'];
    yield 'initials' => ['initials', 'My Awesome Site', 'mas'];
    yield 'initials capped' => ['initials', 'a b c d e', 'abcd'];
    yield 'initials empty' => ['initials', '!!!', ''];
    // An unknown transform passes the value through unchanged.
    yield 'unknown passthrough' => ['bogus', 'As Is', 'As Is'];
  }

  #[DataProvider('dataProviderSupports')]
  public function testSupports(string $name, bool $expected): void {
    $this->assertSame($expected, Transform::supports($name));
  }

  public static function dataProviderSupports(): \Iterator {
    yield 'machine (str2name)' => ['machine', TRUE];
    yield 'abbreviation (str2name)' => ['abbreviation', TRUE];
    yield 'initials (extra)' => ['initials', TRUE];
    yield 'host (extra)' => ['host', TRUE];
    yield 'unknown' => ['bogus', FALSE];
    yield 'empty' => ['', FALSE];
  }

  public function testNames(): void {
    $names = Transform::names();

    $this->assertContains('machine', $names);
    $this->assertContains('initials', $names);
    $this->assertContains('host', $names);
    $this->assertNotContains('bogus', $names);
  }

}
