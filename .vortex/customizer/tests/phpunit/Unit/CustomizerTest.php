<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit;

use DrevOps\Customizer\Builder\Form;
use DrevOps\Customizer\Builder\PanelBuilder;
use DrevOps\Customizer\Customizer;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Customizer facade.
 */
#[CoversClass(Customizer::class)]
#[Group('customizer')]
final class CustomizerTest extends TestCase {

  public function testCollect(): void {
    $answers = $this->customizer()->collect('{"name":"Acme"}', 'dir', FALSE, '1.0');

    $this->assertSame('Acme', $answers->value('name'));
    // "machine" is derived from "name".
    $this->assertSame('acme', $answers->value('machine'));
  }

  public function testSchema(): void {
    $this->assertArrayHasKey('prompts', $this->customizer()->schema());
  }

  public function testAgentHelp(): void {
    $this->assertStringContainsString('name', $this->customizer()->agentHelp());
  }

  public function testValidate(): void {
    $this->assertSame([], $this->customizer()->validate(['name' => 'Acme']));
    $this->assertNotSame([], $this->customizer()->validate(['bogus' => 'x']));
  }

  public function testAccessors(): void {
    $customizer = $this->customizer();

    $this->assertSame('Demo', $customizer->config()->title);
    $this->assertInstanceOf(Engine::class, $customizer->engine());
    $this->assertInstanceOf(HandlerRegistry::class, $customizer->registry());
  }

  /**
   * A customizer over a small in-memory config.
   */
  protected function customizer(): Customizer {
    $config = Form::create('Demo')
      ->panel('p', 'p', function (PanelBuilder $panel): void {
        $panel->text('name')->required();
        $panel->text('machine')->derive(['template' => '{{name}}', 'transform' => 'machine']);
      })
      ->build();

    return new Customizer($config, [], 'TEST_');
  }

}
