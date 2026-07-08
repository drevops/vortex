<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Fixup;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests conditional gating and post-submit fix-ups in the engine.
 */
#[CoversClass(Engine::class)]
#[Group('engine')]
final class EngineConditionalTest extends TestCase {

  public function testInactiveFieldExcluded(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('theme')->default('olivero');
          $p->text('custom_theme')->default('mytheme')->when(new Condition('theme', eq: 'custom'));
        })
        ->build()
    );

    $answers = $engine->collect([], new Context());
    $this->assertArrayHasKey('theme', $answers);
    $this->assertArrayNotHasKey('custom_theme', $answers);

    $answers = $engine->collect(['theme' => 'custom'], new Context());
    $this->assertArrayHasKey('custom_theme', $answers);
    $this->assertSame('mytheme', $answers['custom_theme']);
  }

  public function testForceFixupAutoResolves(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('provision')->default('database');
          $p->text('database_source')->default('url');
        })
        ->fixup(new Fixup(set: 'database_source', to: 'none', when: new Condition('provision', eq: 'profile')))
        ->build()
    );

    $this->assertSame('url', $engine->collect([], new Context())['database_source']);
    // No input for database_source: the fix-up resolves it without prompting.
    $this->assertSame('none', $engine->collect(['provision' => 'profile'], new Context())['database_source']);
  }

  public function testMultiFieldConditional(): void {
    // A when can depend on any number of fields via all / any / not.
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('a')->default('x');
          $p->text('b')->default('y');
          $p->text('c')->default('z')->when(Condition::all(new Condition('a', eq: 'x'), new Condition('b', eq: 'y')));
        })
        ->build()
    );

    // Both conditions hold: c is active.
    $this->assertArrayHasKey('c', $engine->collect([], new Context()));

    // One condition fails: c is gated out.
    $this->assertArrayNotHasKey('c', $engine->collect(['b' => 'other'], new Context()));
  }

  public function testMergeCustomFixup(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('profile')->default('standard');
          $p->text('profile_custom')->default('');
        })
        ->fixup(new Fixup(set: 'profile', from: 'profile_custom', when: new Condition('profile', eq: 'custom')))
        ->build()
    );

    $answers = $engine->collect(['profile' => 'custom', 'profile_custom' => 'my_profile'], new Context());
    $this->assertSame('my_profile', $answers['profile']);
  }

  public function testCascadingDeactivation(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('a')->default('x');
          $p->text('b')->default('y')->when(new Condition('a', eq: 'x'));
          $p->text('c')->default('z')->when(new Condition('b', eq: 'y'));
        })
        ->build()
    );

    $this->assertSame(['a' => 'x', 'b' => 'y', 'c' => 'z'], $engine->collect([], new Context()));
    $this->assertSame(['a' => 'off'], $engine->collect(['a' => 'off'], new Context()));
  }

  /**
   * Build an engine over the given config with no handlers.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration.
   */
  protected function engine(Config $config): Engine {
    return new Engine($config, new HandlerRegistry());
  }

}
