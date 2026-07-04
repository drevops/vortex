<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Engine;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Engine\EngineException;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Tests\Fixtures\Handler\Spy;
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
    $engine = $this->engine([
      ['id' => 'p', 'fields' => [['id' => 'spy'], ['id' => 'plain']]],
    ]);

    $answers = $engine->run([], new Context('project', [], TRUE));

    // The discovered value flows through transform().
    $this->assertSame('discovered!', $answers['spy']);
    // A field with no handler falls back to its default.
    $this->assertSame('', $answers['plain']);
    // Lifecycle order per field, then a separate process pass over all answers.
    $this->assertSame(['discover', 'validate', 'transform', 'process:spy,plain'], Spy::$calls);
  }

  public function testSuppliedInputWins(): void {
    $engine = $this->engine([['id' => 'p', 'fields' => [['id' => 'spy']]]]);

    $answers = $engine->run(['spy' => 'given'], new Context('project', [], TRUE));

    $this->assertSame('given!', $answers['spy']);
    // Input present: discovery is skipped.
    $this->assertSame(['validate', 'transform', 'process:spy'], Spy::$calls);
  }

  public function testDefaultUsedWithoutUpdate(): void {
    $engine = $this->engine([['id' => 'p', 'fields' => [['id' => 'spy', 'default' => 'seed']]]]);

    $answers = $engine->run([], new Context('project', [], FALSE));

    $this->assertSame('seed!', $answers['spy']);
    // Not update mode: discovery is skipped and the default is used.
    $this->assertSame(['validate', 'transform', 'process:spy'], Spy::$calls);
  }

  public function testInvalidValueThrows(): void {
    $engine = $this->engine([['id' => 'p', 'fields' => [['id' => 'machine_name']]]]);

    $this->expectException(EngineException::class);
    $this->expectExceptionMessage('Invalid value for field "machine_name"');
    // The MachineName fixture rejects the empty-string text default.
    $engine->run([], new Context('project', [], FALSE));
  }

  /**
   * Build an engine over the given panels wired to the fixture handlers.
   *
   * @param array<int,array<string,mixed>> $panels
   *   The panels to configure.
   */
  protected function engine(array $panels): Engine {
    $config = (new ConfigLoader())->fromArray(['panels' => $panels]);
    $registry = new HandlerRegistry(['DrevOps\\Customizer\\Tests\\Fixtures\\Handler']);

    return new Engine($config, $registry);
  }

}
