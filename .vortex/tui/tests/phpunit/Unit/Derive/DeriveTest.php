<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Derive;

use DrevOps\Tui\Config\ConfigException;
use DrevOps\Tui\Derive\Derive;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the derive rule object.
 */
#[CoversClass(Derive::class)]
#[Group('derive')]
final class DeriveTest extends TestCase {

  /**
   * Templates interpolate and transforms normalize.
   *
   * @param \DrevOps\Tui\Derive\Derive $derive
   *   The rule.
   * @param array<string,mixed> $values
   *   The current values.
   * @param string $expected
   *   The expected derived value.
   */
  #[DataProvider('dataProviderCompute')]
  public function testCompute(Derive $derive, array $values, string $expected): void {
    $this->assertSame($expected, $derive->compute($values));
  }

  /**
   * Data provider for testCompute().
   *
   * @return \Iterator<string,array{\DrevOps\Tui\Derive\Derive,array<string,mixed>,string}>
   *   Rules, values and expected results.
   */
  public static function dataProviderCompute(): \Iterator {
    yield 'plain interpolation' => [new Derive('{{name}}'), ['name' => 'Acme Site'], 'Acme Site'];
    yield 'spaced token' => [new Derive('{{ name }}.com'), ['name' => 'acme'], 'acme.com'];
    yield 'missing token resolves empty' => [new Derive('a-{{nope}}-b'), [], 'a--b'];
    yield 'non-scalar token resolves empty' => [new Derive('{{list}}'), ['list' => ['x']], ''];
    yield 'machine' => [new Derive('{{name}}', 'machine'), ['name' => 'My Site! 2'], 'my_site_2'];
    yield 'host' => [new Derive('{{name}}', 'host'), ['name' => 'My_Site.Com'], 'my-site.com'];
    yield 'lower' => [new Derive('{{name}}', 'lower'), ['name' => 'HeLLo'], 'hello'];
    yield 'initials' => [new Derive('{{name}}', 'initials'), ['name' => 'My Awesome Site'], 'mas'];
    yield 'kebab (str2name)' => [new Derive('{{name}}', 'kebab'), ['name' => 'My Site'], 'my-site'];
  }

  public function testUnknownTransformThrows(): void {
    $this->expectException(ConfigException::class);
    $this->expectExceptionMessage('Unknown derive transform "bogus".');

    new Derive('{{name}}', 'bogus');
  }

  public function testToArray(): void {
    $this->assertSame(['template' => '{{name}}'], (new Derive('{{name}}'))->toArray());
    $this->assertSame(['template' => '{{name}}', 'transform' => 'machine'], (new Derive('{{name}}', 'machine'))->toArray());
  }

}
