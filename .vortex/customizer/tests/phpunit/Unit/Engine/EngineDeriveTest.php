<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Engine;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
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

    $answers = $engine->run(['name' => 'Acme Site'], new Context());

    $this->assertSame('acme_site', $answers['machine']);
    $this->assertSame('acme-site.com', $answers['domain']);
    $this->assertSame('derived', $engine->provenance()['machine']);
    $this->assertSame('edited', $engine->provenance()['name']);
  }

  public function testOverrideHoldsWhileFollowersUpdate(): void {
    $engine = $this->engine();

    // Machine is pinned; the domain still follows the pinned machine, not name.
    $answers = $engine->run(['name' => 'Acme Site', 'machine' => 'custom'], new Context());

    $this->assertSame('custom', $answers['machine']);
    $this->assertSame('custom.com', $answers['domain']);
    $this->assertSame('override', $engine->provenance()['machine']);
    $this->assertSame('derived', $engine->provenance()['domain']);
  }

  public function testResetRelinks(): void {
    $engine = $this->engine();

    // Pinned on the first run.
    $engine->run(['name' => 'Acme', 'machine' => 'pinned'], new Context());
    // Re-running without the machine input relinks (reset) and re-derives.
    $answers = $engine->run(['name' => 'Acme'], new Context());

    $this->assertSame('acme', $answers['machine']);
    $this->assertSame('derived', $engine->provenance()['machine']);
  }

  /**
   * Build an engine with a name -> machine -> domain derivation chain.
   */
  protected function engine(): Engine {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'name', 'default' => ''],
        ['id' => 'machine', 'default' => '', 'derive' => ['template' => '{{name}}', 'transform' => 'machine']],
        ['id' => 'domain', 'default' => '', 'derive' => ['template' => '{{machine}}.com', 'transform' => 'host']],
      ]]],
    ]);

    return new Engine($config, new HandlerRegistry());
  }

}
