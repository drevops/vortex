<?php

use DrevOps\DevTool\Docker\DockerCommand;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\DevTool\Docker\DockerCommand
 */
class DockerCommandTest extends TestCase {

  /**
   * @covers ::__construct
   * @covers ::getKeyword
   * @covers ::getArguments
   */
  public function testValidCommandCreation(): void {
    $command = new DockerCommand('RUN', 'apt-get update');
    $this->assertEquals('RUN', $command->getKeyword());
    $this->assertEquals('apt-get update', $command->getArguments());
  }

  /**
   * @covers ::__construct
   * @covers ::getKeyword
   * @covers ::getArguments
   */
  public function testInvalidCommandCreation(): void {
    $this->expectException(Exception::class);
    new DockerCommand('INVALIDKEYWORD', 'some arguments');
  }

  /**
   * @covers ::__construct
   * @covers ::getKeyword
   * @covers ::getArguments
   */
  public function testValidCommandWithMultipleArguments(): void {
    $command = new DockerCommand('ADD', '/source /destination');
    $this->assertEquals('ADD', $command->getKeyword());
    $this->assertEquals('/source /destination', $command->getArguments());
  }

}
