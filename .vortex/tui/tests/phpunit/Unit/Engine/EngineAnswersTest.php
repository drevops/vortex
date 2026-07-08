<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
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
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('name')->default('');
        $p->text('machine')->default('')->derive(['template' => '{{name}}', 'transform' => 'machine']);
        $p->text('gone')->default('x')->when(['field' => 'name', 'eq' => 'never']);
      })
      ->build();
    $engine = new Engine($config, new HandlerRegistry());

    $engine->collect(['name' => 'Acme Site'], new Context());
    $answers = $engine->answers();

    $this->assertSame('Acme Site', $answers->value('name'));
    $this->assertSame('acme_site', $answers->value('machine'));
    $this->assertSame('edited', $answers->provenanceOf('name'));
    $this->assertSame('derived', $answers->provenanceOf('machine'));
    $this->assertFalse($answers->has('gone'));
  }

  public function testEmittedSetValidatesAgainstSchema(): void {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('name')->required()->default('Acme');
        $p->select('profile')->default('standard')->option('standard')->option('minimal');
      })
      ->build();
    $engine = new Engine($config, new HandlerRegistry());

    $engine->collect([], new Context());

    $errors = (new SchemaValidator($config))->validate($engine->answers()->values);
    $this->assertSame([], $errors);
  }

}
