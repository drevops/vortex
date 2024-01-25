<?php

namespace DrevOps\DevTool\Tests\Unit\Command;

use DrevOps\DevTool\Tests\Traits\AssertTrait;
use DrevOps\DevTool\Tests\Traits\MockTrait;
use DrevOps\DevTool\Tests\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class CommandTestCase.
 *
 * Base class to unit test commands.
 */
abstract class CommandTestCase extends TestCase {

  use AssertTrait;
  use ReflectionTrait;
  use MockTrait;

  /**
   * CommandTester instance.
   */
  protected CommandTester $commandTester;

  /**
   * Path to fixtures directory.
   */
  protected string $fixturesDir = __DIR__ . '/../../Fixtures';

  /**
   * Run main() with optional arguments.
   *
   * @param string|object $object_or_class
   *   Object or class name.
   * @param array<string> $input
   *   Optional array of input arguments.
   * @param array<string, string> $options
   *   Optional array of options. See CommandTester::execute() for details.
   *
   * @return array<string>
   *   Array of output lines.
   */
  protected function runExecute(string|object $object_or_class, array $input = [], array $options = []): array {
    $application = new Application();
    /** @var \Symfony\Component\Console\Command\Command $instance */
    $instance = is_object($object_or_class) ? $object_or_class : new $object_or_class();
    $application->add($instance);

    /** @var string $name */
    $name = $this->getProtectedValue($instance, 'defaultName');
    $command = $application->find($name);
    $this->commandTester = new CommandTester($command);

    $this->commandTester->execute($input, $options);

    return explode(PHP_EOL, $this->commandTester->getDisplay());
  }

}
