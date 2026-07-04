<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\VortexCli\Handler\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Name handler.
 */
#[CoversClass(Name::class)]
#[Group('handler')]
final class NameTest extends TestCase {

  public function testValidate(): void {
    $handler = new Name();
    $field = new Field('name', 'Name', '', FieldType::Text, '');

    $this->assertNull($handler->validate($field, 'Acme'));
    $this->assertSame('The site name is required.', $handler->validate($field, ''));
    $this->assertSame('The site name is required.', $handler->validate($field, '   '));
  }

  public function testTransformTrims(): void {
    $handler = new Name();
    $field = new Field('name', 'Name', '', FieldType::Text, '');

    $this->assertSame('Acme', $handler->transform($field, '  Acme  '));
  }

}
