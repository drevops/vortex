<?php

namespace DrevOps\DevTool\Tests\Unit\Docker;

use DrevOps\DevTool\Docker\DockerfileParser;
use PHPUnit\Framework\TestCase;

/**
 *
 * @coversDefaultClass \DrevOps\DevTool\Docker\DockerfileParser
 */
class DockerfileParserTest extends TestCase {

  /**
   * @covers ::parse
   */
  public function testParseSimpleCommands(): void {
    $dockerfile = __DIR__ . '/Dockerfile.test';
    file_put_contents($dockerfile, "FROM php:7.4-cli\nRUN apt-get update");

    $commands = DockerfileParser::parse($dockerfile);
    $this->assertCount(2, $commands);
    $this->assertEquals('FROM', $commands[0]->getKeyword());
    $this->assertEquals('php:7.4-cli', $commands[0]->getArguments());
    $this->assertEquals('RUN', $commands[1]->getKeyword());
    $this->assertEquals('apt-get update', $commands[1]->getArguments());

    unlink($dockerfile);
  }

  /**
   * @covers ::parse
   */
  public function testParseMultilineCommands(): void {
    $dockerfile = __DIR__ . '/Dockerfile.test';
    file_put_contents($dockerfile, "RUN apt-get update \\\n   && apt-get install -y git");

    $commands = DockerfileParser::parse($dockerfile);
    $this->assertCount(1, $commands);
    $this->assertEquals('RUN', $commands[0]->getKeyword());
    $this->assertEquals('apt-get update     && apt-get install -y git', $commands[0]->getArguments());

    unlink($dockerfile);
  }

  /**
   * @covers ::parse
   */
  public function testParseWithInvalidCommands(): void {
    $dockerfile = __DIR__ . '/Dockerfile.test';
    file_put_contents($dockerfile, "INVALID INSTRUCTION\nRUN apt-get update");

    $commands = DockerfileParser::parse($dockerfile);
    $this->assertCount(0, $commands);

    unlink($dockerfile);
  }

  /**
   * @covers ::parse
   */
  public function testParseWithEmptyFile(): void {
    $dockerfile = __DIR__ . '/Dockerfile.test';
    file_put_contents($dockerfile, "");

    $commands = DockerfileParser::parse($dockerfile);
    $this->assertEmpty($commands);

    unlink($dockerfile);
  }

  /**
   * @covers ::parse
   */
  public function testParseWithIncorrectFormat(): void {
    $dockerfile = __DIR__ . '/Dockerfile.test';
    file_put_contents($dockerfile, "RUNwithoutSpace apt-get update");

    $commands = DockerfileParser::parse($dockerfile);
    $this->assertEmpty($commands);

    unlink($dockerfile);
  }

}
