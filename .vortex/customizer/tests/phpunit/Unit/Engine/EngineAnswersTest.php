<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Config\ConfigLoader;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\Tui\Schema\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the engine's answers model and its schema-validity.
 */
#[CoversClass(Engine::class)]
#[Group('engine')]
final class EngineAnswersTest extends TestCase {

  public function testAnswersModelReflectsRun(): void {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'name', 'default' => ''],
        ['id' => 'machine', 'default' => '', 'derive' => ['template' => '{{name}}', 'transform' => 'machine']],
        ['id' => 'gone', 'default' => 'x', 'when' => ['field' => 'name', 'eq' => 'never']],
      ]]],
    ]);
    $engine = new Engine($config, new HandlerRegistry());

    $engine->run(['name' => 'Acme Site'], new Context());
    $answers = $engine->answers();

    $this->assertSame('Acme Site', $answers->value('name'));
    $this->assertSame('acme_site', $answers->value('machine'));
    $this->assertSame('edited', $answers->provenanceOf('name'));
    $this->assertSame('derived', $answers->provenanceOf('machine'));
    $this->assertFalse($answers->has('gone'));
  }

  public function testEmittedSetValidatesAgainstSchema(): void {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'name', 'type' => 'text', 'required' => TRUE, 'default' => 'Acme'],
        ['id' => 'profile', 'type' => 'select', 'default' => 'standard', 'options' => [['value' => 'standard'], ['value' => 'minimal']]],
      ]]],
    ]);
    $engine = new Engine($config, new HandlerRegistry());

    $engine->run([], new Context());

    $errors = (new SchemaValidator($config))->validate($engine->answers()->toArray());
    $this->assertSame([], $errors);
  }

}
