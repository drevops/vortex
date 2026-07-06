<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Customizer;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\Context;
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

  public function testFromFilesLoadsConfig(): void {
    $customizer = Customizer::fromFiles([__DIR__ . '/../Fixtures/config/valid.yml']);

    $this->assertSame('Demo', $customizer->config()->title);
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

  public function testProcess(): void {
    // No handler namespaces are registered, so process() runs without error
    // and applies nothing.
    $this->customizer()->process(['name' => 'Acme'], new Context('dir'));

    $this->addToAssertionCount(1);
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
    $config = (new ConfigLoader())->fromArray([
      'title' => 'Demo',
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'name', 'type' => 'text', 'required' => TRUE],
        ['id' => 'machine', 'type' => 'text', 'derive' => ['template' => '{{name}}', 'transform' => 'machine']],
      ]]],
    ]);

    return new Customizer($config, [], 'TEST_');
  }

}
