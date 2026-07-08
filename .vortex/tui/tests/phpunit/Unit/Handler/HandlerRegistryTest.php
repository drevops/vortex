<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Handler;

use DrevOps\Tui\Handler\HandlerInterface;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\AbstractHandler;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\Tui\Tests\Fixtures\Handler\MachineName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the handler registry and name-based auto-discovery.
 */
#[CoversClass(HandlerRegistry::class)]
#[CoversClass(AbstractHandler::class)]
#[CoversClass(Context::class)]
#[Group('handler')]
final class HandlerRegistryTest extends TestCase {

  public function testResolvesByName(): void {
    $registry = $this->registry();

    $handler = $registry->get('machine_name');
    $this->assertInstanceOf(MachineName::class, $handler);
    // Resolved handlers are cached and returned on subsequent calls.
    $this->assertSame($handler, $registry->get('machine_name'));
  }

  public function testResolvesViaAddedNamespace(): void {
    $registry = new HandlerRegistry();
    $this->assertNotInstanceOf(HandlerInterface::class, $registry->get('machine_name'));

    // Surrounding backslashes are tolerated and normalized away.
    $registry->addNamespace('\\DrevOps\\Tui\\Tests\\Fixtures\\Handler\\');
    $this->assertInstanceOf(MachineName::class, $registry->get('machine_name'));
  }

  public function testHandlerBehaviour(): void {
    $handler = $this->registry()->get('machine_name');
    $this->assertInstanceOf(MachineName::class, $handler);
    $field = new Field('machine_name', 'Machine name', '', FieldType::Text, '');

    $this->assertSame('acme', $handler->transform($field, 'ACME'));
    $this->assertNull($handler->validate($field, 'acme'));
    $this->assertSame('A machine name is required.', $handler->validate($field, ''));
  }

  public function testBaseHandlerDefaults(): void {
    $handler = new class extends AbstractHandler {};
    $field = new Field('anything', 'Anything', '', FieldType::Text, '');
    $context = new Context('project', ['name' => 'Acme'], TRUE);

    $this->assertNull($handler->discover($field, $context));
    $this->assertNull($handler->validate($field, 'unchecked'));
    $this->assertSame('kept', $handler->transform($field, 'kept'));
  }

  public function testUnknownFieldReturnsNull(): void {
    $this->assertNotInstanceOf(HandlerInterface::class, $this->registry()->get('does_not_exist'));
  }

  /**
   * Build a registry scoped to the fixture handler namespace.
   */
  protected function registry(): HandlerRegistry {
    return new HandlerRegistry(['DrevOps\\Tui\\Tests\\Fixtures\\Handler']);
  }

}
