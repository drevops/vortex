<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Answers\Provenance;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests derived values, overrides and provenance in the engine.
 */
#[CoversClass(Engine::class)]
#[Group('engine')]
final class EngineDeriveTest extends TestCase {

  public function testDerivedFollowsSource(): void {
    $engine = $this->engine();

    $answers = $engine->collect(['name' => 'Acme Site'], new Context());

    $this->assertSame('acme_site', $answers['machine']);
    $this->assertSame('acme-site.com', $answers['domain']);
    $this->assertSame(Provenance::Derived, $engine->provenance()['machine']);
    $this->assertSame(Provenance::Edited, $engine->provenance()['name']);
  }

  public function testOverrideHoldsWhileFollowersUpdate(): void {
    $engine = $this->engine();

    // Machine is pinned; the domain still follows the pinned machine, not name.
    $answers = $engine->collect(['name' => 'Acme Site', 'machine' => 'custom'], new Context());

    $this->assertSame('custom', $answers['machine']);
    $this->assertSame('custom.com', $answers['domain']);
    $this->assertSame(Provenance::Override, $engine->provenance()['machine']);
    $this->assertSame(Provenance::Derived, $engine->provenance()['domain']);
  }

  public function testResetRelinks(): void {
    $engine = $this->engine();

    // Pinned on the first run.
    $engine->collect(['name' => 'Acme', 'machine' => 'pinned'], new Context());
    // Re-running without the machine input relinks (reset) and re-derives.
    $answers = $engine->collect(['name' => 'Acme'], new Context());

    $this->assertSame('acme', $answers['machine']);
    $this->assertSame(Provenance::Derived, $engine->provenance()['machine']);
  }

  /**
   * Build an engine with a name -> machine -> domain derivation chain.
   */
  protected function engine(): Engine {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('name')->default('');
        $p->text('machine')->default('')->derive(new Derive('{{name}}', 'machine'));
        $p->text('domain')->default('')->derive(new Derive('{{machine}}.com', 'host'));
      })
      ->build();

    return new Engine($config, new HandlerRegistry());
  }

}
