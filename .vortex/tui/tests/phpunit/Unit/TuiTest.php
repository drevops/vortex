<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Tui;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Tui facade.
 */
#[CoversClass(Tui::class)]
#[Group('tui')]
final class TuiTest extends TestCase {

  public function testCollect(): void {
    $answers = $this->tui()->collect('{"name":"Acme"}', 'dir', FALSE, '1.0');

    $this->assertSame('Acme', $answers->value('name'));
    // "machine" is derived from "name".
    $this->assertSame('acme', $answers->value('machine'));
  }

  public function testSchema(): void {
    $this->assertArrayHasKey('prompts', $this->tui()->schema());
  }

  public function testAgentHelp(): void {
    $this->assertStringContainsString('name', $this->tui()->agentHelp());
  }

  public function testEnvPrefix(): void {
    $config = Form::create('Demo')
      ->panel('p', 'p', function (PanelBuilder $panel): void {
        $panel->text('name');
      })
      ->build();

    // No prefix anywhere falls back to the package default.
    $this->assertStringContainsString('TUI_<ID>', (new Tui($config))->agentHelp());
    // A constructor prefix wins.
    $this->assertStringContainsString('ARG_<ID>', (new Tui($config, [], 'ARG_'))->agentHelp());

    $config = Form::create('Demo')
      ->envPrefix('FORM_')
      ->panel('p', 'p', function (PanelBuilder $panel): void {
        $panel->text('name');
      })
      ->build();

    // The form-declared prefix is used unless the constructor overrides it.
    $this->assertStringContainsString('FORM_<ID>', (new Tui($config))->agentHelp());
    $this->assertStringContainsString('ARG_<ID>', (new Tui($config, [], 'ARG_'))->agentHelp());
  }

  public function testValidate(): void {
    $this->assertSame([], $this->tui()->validate(['name' => 'Acme']));
    $this->assertNotSame([], $this->tui()->validate(['bogus' => 'x']));
  }

  public function testAccessors(): void {
    $tui = $this->tui();

    $this->assertSame('Demo', $tui->config()->title);
    $this->assertInstanceOf(Engine::class, $tui->engine());
    $this->assertInstanceOf(HandlerRegistry::class, $tui->registry());
  }

  /**
   * A TUI over a small in-memory config.
   */
  protected function tui(): Tui {
    $config = Form::create('Demo')
      ->panel('p', 'p', function (PanelBuilder $panel): void {
        $panel->text('name')->required();
        $panel->text('machine')->derive(['template' => '{{name}}', 'transform' => 'machine']);
      })
      ->build();

    return new Tui($config, [], 'TEST_');
  }

}
