<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the base handler's no-op defaults.
 */
#[CoversClass(AbstractHandler::class)]
#[Group('handler')]
final class AbstractHandlerTest extends TestCase {

  public function testNoOpDefaults(): void {
    $handler = new class() extends AbstractHandler {};
    $field = new Field('x', 'X', '', FieldType::Text, 'd');
    $context = new Context('dir');

    $this->assertNull($handler->default($field, $context));
    $this->assertNull($handler->discover($field, $context));
    $this->assertNull($handler->validate($field, 'v'));
    $this->assertSame('v', $handler->transform($field, 'v'));

    // process() is a no-op; assert it runs without error.
    $handler->process($field, 'v', $context);
    $this->addToAssertionCount(1);
  }

}
