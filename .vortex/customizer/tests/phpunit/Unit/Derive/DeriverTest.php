<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Derive;

use DrevOps\Customizer\Derive\Deriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the deriver.
 */
#[CoversClass(Deriver::class)]
#[Group('derive')]
final class DeriverTest extends TestCase {

  public function testInterpolationAndTransform(): void {
    $rules = ['machine' => ['template' => '{{name}}', 'transform' => 'machine']];

    $out = (new Deriver())->derive($rules, ['name' => 'Acme Site', 'machine' => ''], []);

    $this->assertSame('acme_site', $out['machine']);
  }

  public function testChainSettles(): void {
    $rules = [
      'machine' => ['template' => '{{name}}', 'transform' => 'machine'],
      'domain' => ['template' => '{{ machine }}.com', 'transform' => 'host'],
    ];

    $out = (new Deriver())->derive($rules, ['name' => 'Acme Site', 'machine' => '', 'domain' => ''], []);

    $this->assertSame('acme_site', $out['machine']);
    $this->assertSame('acme-site.com', $out['domain']);
  }

  public function testOverriddenNotRecomputed(): void {
    $rules = ['machine' => ['template' => '{{name}}', 'transform' => 'machine']];

    $out = (new Deriver())->derive($rules, ['name' => 'Acme', 'machine' => 'pinned'], ['machine' => TRUE]);

    $this->assertSame('pinned', $out['machine']);
  }

  public function testMissingTokenResolvesEmpty(): void {
    $rules = ['x' => ['template' => 'a-{{nope}}-b']];

    $out = (new Deriver())->derive($rules, ['x' => ''], []);

    $this->assertSame('a--b', $out['x']);
  }

  /**
   * Named transforms normalize the interpolated value.
   *
   * @param string $transform
   *   The transform name.
   * @param string $input
   *   The source value.
   * @param string $expected
   *   The expected derived value.
   */
  #[DataProvider('dataProviderTransforms')]
  public function testTransforms(string $transform, string $input, string $expected): void {
    $rules = ['x' => ['template' => '{{src}}', 'transform' => $transform]];

    $out = (new Deriver())->derive($rules, ['src' => $input, 'x' => ''], []);

    $this->assertSame($expected, $out['x']);
  }

  /**
   * Data provider for testTransforms().
   *
   * @return \Iterator<string,array{string,string,string}>
   *   Transform name, input and expected output.
   */
  public static function dataProviderTransforms(): \Iterator {
    yield 'lower' => ['lower', 'HeLLo', 'hello'];
    yield 'upper' => ['upper', 'HeLLo', 'HELLO'];
    yield 'machine' => ['machine', 'My Site! 2', 'my_site_2'];
    yield 'host' => ['host', 'My_Site.Com', 'my-site.com'];
    yield 'abbreviation' => ['abbreviation', 'My Awesome Site', 'mas'];
    yield 'abbreviation capped' => ['abbreviation', 'a b c d e', 'abcd'];
    yield 'none' => ['', 'As Is', 'As Is'];
    yield 'unknown passthrough' => ['bogus', 'As Is', 'As Is'];
  }

}
