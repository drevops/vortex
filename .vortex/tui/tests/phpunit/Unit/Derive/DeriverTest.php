<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Derive;

use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Derive\Deriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the deriver's fixpoint settling.
 */
#[CoversClass(Deriver::class)]
#[Group('derive')]
final class DeriverTest extends TestCase {

  public function testInterpolationAndTransform(): void {
    $rules = ['machine' => new Derive('{{name}}', 'machine')];

    $out = (new Deriver())->derive($rules, ['name' => 'Acme Site', 'machine' => ''], []);

    $this->assertSame('acme_site', $out['machine']);
  }

  public function testChainSettles(): void {
    $rules = [
      'machine' => new Derive('{{name}}', 'machine'),
      'domain' => new Derive('{{ machine }}.com', 'host'),
    ];

    $out = (new Deriver())->derive($rules, ['name' => 'Acme Site', 'machine' => '', 'domain' => ''], []);

    $this->assertSame('acme_site', $out['machine']);
    $this->assertSame('acme-site.com', $out['domain']);
  }

  public function testOverriddenNotRecomputed(): void {
    $rules = ['machine' => new Derive('{{name}}', 'machine')];

    $out = (new Deriver())->derive($rules, ['name' => 'Acme', 'machine' => 'pinned'], ['machine' => TRUE]);

    $this->assertSame('pinned', $out['machine']);
  }

  public function testMissingTokenResolvesEmpty(): void {
    $rules = ['x' => new Derive('a-{{nope}}-b')];

    $out = (new Deriver())->derive($rules, ['x' => ''], []);

    $this->assertSame('a--b', $out['x']);
  }

}
