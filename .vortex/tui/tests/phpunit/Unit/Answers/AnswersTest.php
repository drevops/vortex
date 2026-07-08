<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Answers;

use DrevOps\Tui\Answers\Answers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the answers model.
 */
#[CoversClass(Answers::class)]
#[Group('answers')]
final class AnswersTest extends TestCase {

  public function testAccessors(): void {
    $answers = new Answers(['name' => 'Acme', 'agree' => TRUE], ['name' => 'edited', 'agree' => 'default']);

    $this->assertTrue($answers->has('name'));
    $this->assertFalse($answers->has('nope'));
    $this->assertSame('Acme', $answers->value('name'));
    $this->assertNull($answers->value('nope'));
    $this->assertSame('edited', $answers->provenanceOf('name'));
    $this->assertSame('default', $answers->provenanceOf('missing'));
    $this->assertSame(['name' => 'Acme', 'agree' => TRUE], $answers->values);
  }

  public function testToJson(): void {
    $answers = new Answers(['name' => 'Acme', 'mods' => ['a', 'b']]);

    $this->assertSame('{"name":"Acme","mods":["a","b"]}', $answers->toJson());
  }

  public function testEmpty(): void {
    $answers = new Answers();

    $this->assertSame([], $answers->values);
    $this->assertSame('default', $answers->provenanceOf('x'));
  }

}
