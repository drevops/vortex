<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Process;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerInterface;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Process\Processor;
use DrevOps\Customizer\Tests\Fixtures\Process\RecordingHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the config-driven processor.
 */
#[CoversClass(Processor::class)]
#[Group('process')]
final class ProcessorTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();
    RecordingHandler::$log = [];
  }

  public function testAppliesInWeightedOrder(): void {
    $config = (new ConfigLoader())->fromArray([
      'processors' => [['id' => 'dot', 'weight' => -1000], ['id' => 'clean', 'weight' => 1000]],
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'a', 'weight' => 20],
        ['id' => 'b', 'weight' => 10],
        ['id' => 'skip', 'weight' => 5],
      ]]],
    ]);

    (new Processor())->apply($config, $this->registry(), ['a' => 'A', 'b' => 'B', 'dot' => 'D', 'clean' => 'C'], new Context('dir'));

    // dot(-1000), b(10), a(20), clean(1000); "skip" is inactive (not answered).
    $this->assertSame(['D', 'B', 'A', 'C'], RecordingHandler::$log);
  }

  public function testEqualWeightsProcessInReverseDeclarationOrder(): void {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [['id' => 'x'], ['id' => 'y'], ['id' => 'z']]]],
    ]);

    (new Processor())->apply($config, $this->registry(), ['x' => 'X', 'y' => 'Y', 'z' => 'Z'], new Context('dir'));

    $this->assertSame(['Z', 'Y', 'X'], RecordingHandler::$log);
  }

  public function testSkipsWhenNoHandler(): void {
    $config = (new ConfigLoader())->fromArray(['panels' => [['id' => 'p', 'fields' => [['id' => 'a']]]]]);

    (new Processor())->apply($config, new HandlerRegistry(), ['a' => 'A'], new Context('dir'));

    $this->assertSame([], RecordingHandler::$log);
  }

  /**
   * A registry that resolves every id to a recording handler.
   */
  protected function registry(): HandlerRegistry {
    return new class() extends HandlerRegistry {

      public function get(string $field_id): HandlerInterface {
        return new RecordingHandler();
      }

    };
  }

}
