<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Config;
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
          $p->text('custom_theme')->default('mytheme')->when(['field' => 'theme', 'eq' => 'custom']);
        })
        ->build()
    );

    $answers = $engine->run([], new Context());
    $this->assertArrayHasKey('theme', $answers);
    $this->assertArrayNotHasKey('custom_theme', $answers);

    $answers = $engine->run(['theme' => 'custom'], new Context());
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
        ->fixup(['when' => ['field' => 'provision', 'eq' => 'profile'], 'set' => 'database_source', 'to' => 'none'])
        ->build()
    );

    $this->assertSame('url', $engine->run([], new Context())['database_source']);
    // No input for database_source: the fix-up resolves it without prompting.
    $this->assertSame('none', $engine->run(['provision' => 'profile'], new Context())['database_source']);
  }

  public function testFixupWithoutTargetIsSkipped(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('a')->default('x');
        })
        // The "when" matches but there is no "set" target: the rule is ignored.
        ->fixup(['when' => ['field' => 'a', 'eq' => 'x']])
        ->build()
    );

    $this->assertSame(['a' => 'x'], $engine->run([], new Context()));
  }

  public function testMultiFieldConditional(): void {
    // A when can depend on any number of fields via all / any / not.
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('a')->default('x');
          $p->text('b')->default('y');
          $p->text('c')->default('z')->when(['all' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]]);
        })
        ->build()
    );

    // Both conditions hold: c is active.
    $this->assertArrayHasKey('c', $engine->run([], new Context()));

    // One condition fails: c is gated out.
    $this->assertArrayNotHasKey('c', $engine->run(['b' => 'other'], new Context()));
  }

  public function testMergeCustomFixup(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('profile')->default('standard');
          $p->text('profile_custom')->default('');
        })
        ->fixup(['when' => ['field' => 'profile', 'eq' => 'custom'], 'set' => 'profile', 'to' => ['field' => 'profile_custom']])
        ->build()
    );

    $answers = $engine->run(['profile' => 'custom', 'profile_custom' => 'my_profile'], new Context());
    $this->assertSame('my_profile', $answers['profile']);
  }

  public function testCascadingDeactivation(): void {
    $engine = $this->engine(
      Form::create('T')
        ->panel('p', 'p', function (PanelBuilder $p): void {
          $p->text('a')->default('x');
          $p->text('b')->default('y')->when(['field' => 'a', 'eq' => 'x']);
          $p->text('c')->default('z')->when(['field' => 'b', 'eq' => 'y']);
        })
        ->build()
    );

    $this->assertSame(['a' => 'x', 'b' => 'y', 'c' => 'z'], $engine->run([], new Context()));
    $this->assertSame(['a' => 'off'], $engine->run(['a' => 'off'], new Context()));
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
