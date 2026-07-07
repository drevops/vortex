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
 * Tests the generic lifecycle engine.
 */
#[CoversClass(Engine::class)]
#[CoversClass(EngineException::class)]
#[Group('engine')]
final class EngineTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();
    Spy::$calls = [];
  }

  public function testDiscoversInUpdateMode(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy');
      $p->text('plain');
    });

    $answers = $engine->run([], new Context('project', [], TRUE));

    // The discovered value flows through transform().
    $this->assertSame('discovered!', $answers['spy']);
    // A field with no handler falls back to its default.
    $this->assertSame('', $answers['plain']);
    // Lifecycle order per field, then a separate process pass over all answers.
    $this->assertSame(['discover', 'validate', 'transform', 'process:spy,plain'], Spy::$calls);
  }

  public function testCollectSkipsProcess(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy');
    });

    $engine->collect([], new Context('project', [], TRUE));

    // collect() runs discover/validate/transform but never process().
    $this->assertSame(['discover', 'validate', 'transform'], Spy::$calls);
    $this->assertSame('discovered!', $engine->answers()->value('spy'));
  }

  public function testDynamicDefaultFromHandler(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('defaulter');
    });

    $answers = $engine->run([], new Context('proj', [], FALSE));

    $this->assertSame('dynamic-proj', $answers['defaulter']);
  }

  public function testDynamicDefaultOverriddenByInput(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('defaulter');
    });

    $answers = $engine->run(['defaulter' => 'given'], new Context('proj', [], FALSE));

    $this->assertSame('given', $answers['defaulter']);
  }

  public function testSuppliedInputWins(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy');
    });

    $answers = $engine->run(['spy' => 'given'], new Context('project', [], TRUE));

    $this->assertSame('given!', $answers['spy']);
    // Input present: discovery is skipped.
    $this->assertSame(['validate', 'transform', 'process:spy'], Spy::$calls);
  }

  public function testDefaultUsedWithoutUpdate(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy')->default('seed');
    });

    $answers = $engine->run([], new Context('project', [], FALSE));

    $this->assertSame('seed!', $answers['spy']);
    // Not update mode: discovery is skipped and the default is used.
    $this->assertSame(['validate', 'transform', 'process:spy'], Spy::$calls);
  }

  public function testInvalidValueThrows(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('machine_name');
    });

    $this->expectException(EngineException::class);
    $this->expectExceptionMessage('Invalid value for field "machine_name"');
    // The MachineName fixture rejects the empty-string text default.
    $engine->run([], new Context('project', [], FALSE));
  }

  /**
   * Build an engine over a single panel wired to the fixture handlers.
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
