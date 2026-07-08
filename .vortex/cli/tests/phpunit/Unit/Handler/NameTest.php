<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handler;

use DrevOps\VortexCli\Handler\Name;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Converter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Name handler contract.
 */
#[CoversClass(Name::class)]
#[Group('handler')]
final class NameTest extends TestCase {

  public function testValidate(): void {
    $validate = $this->handler()->validate();

    $this->assertIsCallable($validate);
    // A label-shaped value passes; anything the label conversion would alter
    // is rejected.
    $this->assertNull($validate(Converter::label('My project')));
    $this->assertSame('Please enter a valid project name.', $validate('my_site'));
  }

  public function testTransformTrims(): void {
    $transform = $this->handler()->transform();

    $this->assertIsCallable($transform);
    $this->assertSame('Acme', $transform('  Acme  '));
  }

  public function testDefaultFromDestination(): void {
    $this->assertSame(Converter::label('my_project'), $this->handler()->default([]));
  }

  /**
   * Build a handler over a fixed destination directory.
   */
  protected function handler(): Name {
    return new Name(new Config(NULL, '/tmp-fixture/my_project', '/tmp-fixture/my_project'));
  }

}
