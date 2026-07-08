<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Handler;

use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\Tui\Tests\Fixtures\Handler\MachineName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests class resolution and static-behaviour discovery by field id.
 */
#[CoversClass(HandlerRegistry::class)]
#[CoversClass(Context::class)]
#[Group('handler')]
final class HandlerRegistryTest extends TestCase {

  public function testResolvesByName(): void {
    $registry = $this->registry();

    $this->assertSame(MachineName::class, $registry->resolve('machine_name'));
    // Resolved classes are cached and returned on subsequent calls.
    $this->assertSame(MachineName::class, $registry->resolve('machine_name'));
    $this->assertNull($registry->resolve('does_not_exist'));
  }

  public function testResolvesViaAddedNamespace(): void {
    $registry = new HandlerRegistry();
    $this->assertNull($registry->resolve('machine_name'));

    // Surrounding backslashes are tolerated and normalized away.
    $registry->addNamespace('\\DrevOps\\Tui\\Tests\\Fixtures\\Handler\\');
    $this->assertSame(MachineName::class, $registry->resolve('machine_name'));
  }

  public function testDiscoversStaticBehaviour(): void {
    $registry = $this->registry();

    $validator = $registry->validator('machine_name');
    $this->assertInstanceOf(\Closure::class, $validator);
    $this->assertNull($validator('acme'));
    $this->assertSame('A machine name is required.', $validator(''));

    $transformer = $registry->transformer('machine_name');
    $this->assertInstanceOf(\Closure::class, $transformer);
    $this->assertSame('acme', $transformer('ACME'));
  }

  public function testAbsentBehaviourResolvesNull(): void {
    $registry = $this->registry();

    // No class at all.
    $this->assertNull($registry->validator('does_not_exist'));
    $this->assertNull($registry->transformer('does_not_exist'));
    // A class without static validate()/transform() offers no behaviour.
    $this->assertNull($registry->validator('plain'));
    $this->assertNull($registry->transformer('plain'));
  }

  /**
   * Build a registry scoped to the fixture namespace.
   */
  protected function registry(): HandlerRegistry {
    return new HandlerRegistry(['DrevOps\\Tui\\Tests\\Fixtures\\Handler']);
  }

}
