<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use DrevOps\VortexCli\Command\SayHelloCommand;

/**
 * Class SayHelloCommandTest.
 *
 * This is a unit test for the SayHelloCommand class.
 */
#[CoversMethod(SayHelloCommand::class, 'execute')]
#[CoversMethod(SayHelloCommand::class, 'configure')]
#[Group('command')]
final class SayHelloCommandTest extends TestCase {

  use ApplicationTrait;
  use AssertArrayTrait;

  public function testExecute(): void {
    $this->applicationInitFromCommand(SayHelloCommand::class);

    $output = $this->applicationRun();
    $this->assertStringContainsString('Hello, Symfony console!', $output);
  }

}
