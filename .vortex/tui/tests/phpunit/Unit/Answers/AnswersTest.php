<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Answers;

use DrevOps\Tui\Answers\Answer;
use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Answers\Provenance;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\FieldType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the answers model.
 */
#[CoversClass(Answers::class)]
#[CoversClass(Answer::class)]
#[Group('answers')]
final class AnswersTest extends TestCase {

  public function testAccessors(): void {
    $answers = new Answers(['name' => 'Acme', 'agree' => TRUE], ['name' => Provenance::Edited, 'agree' => Provenance::Default]);

    $this->assertTrue($answers->has('name'));
    $this->assertFalse($answers->has('nope'));
    $this->assertSame('Acme', $answers->value('name'));
    $this->assertNull($answers->value('nope'));
    $this->assertSame(Provenance::Edited, $answers->provenanceOf('name'));
    $this->assertSame(Provenance::Default, $answers->provenanceOf('missing'));
    $this->assertSame(['name' => 'Acme', 'agree' => TRUE], $answers->values);
  }

  public function testToJson(): void {
    $answers = new Answers(['name' => 'Acme', 'mods' => ['a', 'b']]);

    $this->assertSame('{"name":"Acme","mods":["a","b"]}', $answers->toJson());
  }

  public function testEmpty(): void {
    $answers = new Answers();

    $this->assertSame([], $answers->values);
    $this->assertSame(Provenance::Default, $answers->provenanceOf('x'));
    $this->assertNull($answers->item('x'));
    $this->assertSame('', $answers->toSummary());
  }

  public function testForConfigSnapshotsQuestions(): void {
    $config = Form::create('T')
      ->panel('general', 'General', function (PanelBuilder $p): void {
        $p->text('name', 'Site name')->weight(10);
        $p->text('inactive', 'Inactive');
        $p->panel('adv', 'Advanced', function (PanelBuilder $sp): void {
          $sp->confirm('debug', 'Debug');
        });
      })
      ->build();

    $answers = Answers::forConfig($config, ['name' => 'Acme', 'debug' => TRUE], ['name' => Provenance::Edited]);

    // Snapshots exist only for active questions, in form order.
    $this->assertSame(['name', 'debug'], array_keys($answers->items));

    $name = $answers->item('name');
    $this->assertInstanceOf(Answer::class, $name);
    $this->assertSame('Acme', $name->value);
    $this->assertSame(Provenance::Edited, $name->provenance);
    $this->assertSame('Site name', $name->label);
    $this->assertSame(FieldType::Text, $name->type);
    $this->assertSame(10, $name->weight);
    $this->assertSame(['General'], $name->panels);

    $debug = $answers->item('debug');
    $this->assertInstanceOf(Answer::class, $debug);
    $this->assertSame(Provenance::Default, $debug->provenance);
    $this->assertSame(FieldType::Confirm, $debug->type);
    $this->assertSame(['General', 'Advanced'], $debug->panels);
  }

  public function testToSummaryDelegates(): void {
    $config = Form::create('T')
      ->panel('p', 'P', function (PanelBuilder $p): void {
        $p->text('name', 'Name');
      })
      ->build();

    $summary = Answers::forConfig($config, ['name' => 'Acme'], ['name' => Provenance::Edited])->toSummary();

    $this->assertStringContainsString('P', $summary);
    $this->assertStringContainsString('Name: Acme (edited)', $summary);
  }

}
