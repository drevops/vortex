<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\Tui\Tests\Fixtures\Handler\Spy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the generic lifecycle engine with discovered static behaviour.
 */
#[CoversClass(Engine::class)]
#[CoversClass(EngineException::class)]
#[Group('engine')]
final class EngineTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();
    Spy::$calls = [];
  }

  public function testDiscoveredStaticsRunInOrder(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy')->default('seed');
      $p->text('plain');
    });

    $answers = $engine->collect([], new Context('project'));

    // The default flows through the discovered static transform.
    $this->assertSame('seed!', $answers['spy']);
    // A field with no consumer class falls back to its default untouched.
    $this->assertSame('', $answers['plain']);
    // Lifecycle order per field.
    $this->assertSame(['validate', 'transform'], Spy::$calls);
  }

  public function testSuppliedInputWins(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy');
    });

    $answers = $engine->collect(['spy' => 'given'], new Context('project'));

    $this->assertSame('given!', $answers['spy']);
    $this->assertSame(['validate', 'transform'], Spy::$calls);
  }

  public function testInvalidValueThrows(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('machine_name');
    });

    $this->expectException(EngineException::class);
    $this->expectExceptionMessage('Invalid value for field "machine_name"');
    // The MachineName fixture rejects the empty-string text default.
    $engine->collect([], new Context('project'));
  }

  public function testDiscoveredTransformNormalizes(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('machine_name');
    });

    $answers = $engine->collect(['machine_name' => 'ACME'], new Context('project'));

    $this->assertSame('acme', $answers['machine_name']);
  }

  /**
   * Build an engine over a single panel wired to the fixture namespace.
   *
   * @param \Closure $build
   *   The callback receiving the panel builder to declare its fields.
   */
  protected function engine(\Closure $build): Engine {
    $config = Form::create('T')->panel('p', 'p', $build)->build();
    $registry = new HandlerRegistry(['DrevOps\\Tui\\Tests\\Fixtures\\Handler']);

    return new Engine($config, $registry);
  }

}
