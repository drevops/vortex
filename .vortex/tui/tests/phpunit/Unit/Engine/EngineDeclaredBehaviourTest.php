<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Answers\Provenance;
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
 * Tests field-declared behaviour: closures on the field, handler fallback.
 */
#[CoversClass(Engine::class)]
#[Group('engine')]
final class EngineDeclaredBehaviourTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();
    Spy::$calls = [];
  }

  public function testDeclaredDefaultFromContext(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('name')->default(fn (Context $c): string => 'from-' . basename($c->directory));
    });

    $answers = $engine->collect([], new Context('some/project'));

    $this->assertSame('from-project', $answers['name']);
    $this->assertSame(Provenance::Default, $engine->provenance()['name']);
  }

  public function testDeclaredDefaultOverriddenByInput(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('name')->default(fn (Context $c): string => 'dynamic');
    });

    $this->assertSame(['name' => 'given'], $engine->collect(['name' => 'given'], new Context()));
  }

  public function testDeclaredValidateRejects(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('name')->validate(fn (mixed $v): ?string => $v === 'ok' ? NULL : 'Must be "ok".');
    });

    $this->assertSame(['name' => 'ok'], $engine->collect(['name' => 'ok'], new Context()));

    $this->expectException(EngineException::class);
    $this->expectExceptionMessage('Invalid value for field "name": Must be "ok".');
    $engine->collect(['name' => 'nope'], new Context());
  }

  public function testDeclaredTransformApplies(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('name')->transform(fn (mixed $v): mixed => is_string($v) ? trim($v) : $v);
    });

    $this->assertSame(['name' => 'Acme'], $engine->collect(['name' => '  Acme  '], new Context()));
  }

  public function testDeclaredDiscoverClosure(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('name')->discover(fn (Context $c): string => 'seen-' . basename($c->directory));
    });

    $answers = $engine->collect([], new Context('some/project', [], TRUE));

    $this->assertSame('seen-project', $answers['name']);
    $this->assertSame(Provenance::Detected, $engine->provenance()['name']);
  }

  public function testDeclarationWinsOverHandler(): void {
    // The "spy" field resolves to the Spy fixture class, but the declared
    // closures take precedence over its reusable statics.
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('spy')
        ->validate(fn (mixed $v): ?string => NULL)
        ->transform(fn (mixed $v): mixed => $v . '?');
    });

    $answers = $engine->collect(['spy' => 'declared'], new Context('project'));

    $this->assertSame('declared?', $answers['spy']);
    $this->assertSame([], Spy::$calls);
  }

  /**
   * Build an engine over a single panel wired to the fixture handlers.
   *
   * @param \Closure $build
   *   The callback receiving the panel builder to declare its fields.
   */
  protected function engine(\Closure $build): Engine {
    $config = Form::create('T')->panel('p', 'p', $build)->build();

    return new Engine($config, new HandlerRegistry(['DrevOps\\Tui\\Tests\\Fixtures\\Handler']));
  }

}
