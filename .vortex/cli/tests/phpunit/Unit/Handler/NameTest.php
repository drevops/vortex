<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handler;

use DrevOps\VortexCli\Handler\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Name handler's reusable static behaviour.
 */
#[CoversClass(Name::class)]
#[Group('handler')]
final class NameTest extends TestCase {

  public function testValidate(): void {
    $this->assertNull(Name::validate('Acme'));
    $this->assertSame('The site name is required.', Name::validate(''));
    $this->assertSame('The site name is required.', Name::validate('   '));
  }

  public function testTransformTrims(): void {
    $this->assertSame('Acme', Name::transform('  Acme  '));
  }

}
