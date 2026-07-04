<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use DrevOps\VortexCli\Command\JokeCommand;

/**
 * Class JokeCommandTest.
 *
 * This is a unit test for the JokeCommand class.
 */
#[CoversMethod(JokeCommand::class, 'execute')]
#[CoversMethod(JokeCommand::class, 'configure')]
#[CoversMethod(JokeCommand::class, 'getJoke')]
#[Group('command')]
final class JokeCommandTest extends TestCase {

  use ApplicationTrait;
  use AssertArrayTrait;

  #[DataProvider('dataProviderExecute')]
  public function testExecute(string $content, array $expected_output, bool $expected_fail = FALSE): void {
    $builder = $this->getMockBuilder(JokeCommand::class);
    $builder->onlyMethods(['getContent']);
    $mock = $builder->getMock();
    $mock->method('getContent')->willReturn($content);
    $mock->setName('joke');

    $this->applicationInitFromCommand($mock);
    $output = $this->applicationRun([], [], $expected_fail);
    foreach ($expected_output as $expected_output_string) {
      $this->assertStringContainsString($expected_output_string, $output);
    }
  }

  public static function dataProviderExecute(): \Iterator {
    yield [self::fixturePayload(['setup' => 'Test setup', 'punchline' => 'Test punchline']), ['Test setup', 'Test punchline']];
    yield ['', ['Unable to retrieve a joke.'], TRUE];
    yield ['non-json', ['Unable to retrieve a joke.'], TRUE];
    yield [self::fixturePayload(['setup' => 'Test setup']), ['Unable to retrieve a joke.'], TRUE];
  }

  /**
   * Get a fixture payload.
   *
   * @param array<string, string> $data
   *   Data to be encoded.
   *
   * @return string
   *   Encoded data.
   */
  protected static function fixturePayload(array $data): string {
    $json = json_encode([(object) $data]);

    if ($json === FALSE) {
      throw new \Exception('Unable to encode test data.');
    }

    return $json;
  }

}
