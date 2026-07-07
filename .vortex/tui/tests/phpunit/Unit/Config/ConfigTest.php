<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Config;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Panel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the root config model.
 */
#[CoversClass(Config::class)]
#[Group('config')]
final class ConfigTest extends TestCase {

  public function testFieldsFlattensTreeInOrder(): void {
    $config = new Config('T', 'S', [
      new Panel('a', 'A', '', [new Field('f1', 'F1', '', FieldType::Text, '')], [
        new Panel('b', 'B', '', [new Field('f2', 'F2', '', FieldType::Text, '')]),
      ]),
      new Panel('c', 'C', '', [new Field('f3', 'F3', '', FieldType::Text, '')]),
    ]);

    $ids = array_map(static fn(Field $field): string => $field->id, $config->fields());

    $this->assertSame(['f1', 'f2', 'f3'], $ids);
  }

  public function testFieldFindsByIdAcrossTree(): void {
    $config = new Config('T', 'S', [
      new Panel('a', 'A', '', [new Field('top', 'T', '', FieldType::Text, '')], [
        new Panel('b', 'B', '', [new Field('deep', 'D', '', FieldType::Text, '')]),
      ]),
    ]);

    $this->assertSame('top', $config->field('top')?->id);
    $this->assertSame('deep', $config->field('deep')?->id);
    $this->assertNotInstanceOf(Field::class, $config->field('missing'));
  }

}
