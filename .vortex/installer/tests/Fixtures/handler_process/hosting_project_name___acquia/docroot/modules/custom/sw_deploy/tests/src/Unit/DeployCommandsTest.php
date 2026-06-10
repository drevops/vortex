<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_deploy\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\sw_deploy\Drush\Commands\DeployCommands;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DeployCommands helpers.
 *
 * @package Drupal\sw_deploy\Tests
 */
#[Group('SwDeploy')]
class DeployCommandsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Reset the Settings singleton to an empty instance so environment state
    // does not leak into other tests that share the same process.
    new Settings([]);

    parent::tearDown();
  }

  /**
   * Tests that steps run in the order they are declared.
   */
  public function testRunStepsExecutesInOrder(): void {
    $commands = $this->createCommands();
    $calls = [];

    $steps = [
      'first' => function () use (&$calls): void {
        $calls[] = 'first';
      },
      'second' => function () use (&$calls): void {
        $calls[] = 'second';
      },
      'third' => function () use (&$calls): void {
        $calls[] = 'third';
      },
    ];

    $this->invoke($commands, 'runSteps', ['post-deploy', $steps]);

    $this->assertSame(['first', 'second', 'third'], $calls);
  }

  /**
   * Tests that a failing step aborts the sequence and propagates the exception.
   */
  public function testRunStepsAbortsOnException(): void {
    $commands = $this->createCommands();
    $calls = [];

    $steps = [
      'ok' => function () use (&$calls): void {
        $calls[] = 'ok';
      },
      'boom' => function (): void {
        throw new \RuntimeException('Step failed.');
      },
      'never' => function () use (&$calls): void {
        $calls[] = 'never';
      },
    ];

    $exception = NULL;
    try {
      $this->invoke($commands, 'runSteps', ['post-deploy', $steps]);
    }
    catch (\RuntimeException $e) {
      $exception = $e;
    }

    $this->assertInstanceOf(\RuntimeException::class, $exception);
    $this->assertSame('Step failed.', $exception->getMessage());
    $this->assertSame(['ok'], $calls, 'Steps after a failing step do not run.');
  }

  /**
   * Tests that only modules that are not already enabled are installed.
   */
  public function testInstallModulesInstallsOnlyMissing(): void {
    $handler = $this->createMock(ModuleHandlerInterface::class);
    $handler->method('moduleExists')->willReturnMap([
      ['existing', TRUE],
      ['missing', FALSE],
    ]);

    $installer = $this->createMock(ModuleInstallerInterface::class);
    $installer->expects($this->once())->method('install')->with(['missing']);

    $commands = $this->createCommands($handler, $installer);

    $this->invoke($commands, 'installModules', [['existing', 'missing']]);
  }

  /**
   * Tests that nothing is installed when all modules are already enabled.
   */
  public function testInstallModulesSkipsWhenAllPresent(): void {
    $handler = $this->createMock(ModuleHandlerInterface::class);
    $handler->method('moduleExists')->willReturn(TRUE);

    $installer = $this->createMock(ModuleInstallerInterface::class);
    $installer->expects($this->never())->method('install');

    $commands = $this->createCommands($handler, $installer);

    $this->invoke($commands, 'installModules', [['a', 'b']]);
  }

  /**
   * Tests environment detection.
   */
  #[DataProvider('dataProviderEnvironment')]
  public function testEnvironment(string $value, bool $expected_production): void {
    new Settings(['environment' => $value]);
    $commands = $this->createCommands();

    $this->assertSame($value, $this->invoke($commands, 'environment'));
    $this->assertSame($expected_production, $this->invoke($commands, 'isProduction'));
  }

  /**
   * Data provider for testEnvironment().
   */
  public static function dataProviderEnvironment(): \Iterator {
    yield 'production' => ['prod', TRUE];
    yield 'local' => ['local', FALSE];
    yield 'ci' => ['ci', FALSE];
    yield 'stage' => ['stage', FALSE];
    yield 'dev' => ['dev', FALSE];
  }

  /**
   * Tests that the environment defaults to an empty string when not set.
   */
  public function testEnvironmentDefaultsToEmpty(): void {
    new Settings([]);
    $commands = $this->createCommands();

    $this->assertSame('', $this->invoke($commands, 'environment'));
    $this->assertFalse($this->invoke($commands, 'isProduction'));
  }

  /**
   * Creates a DeployCommands instance with mocked dependencies.
   */
  protected function createCommands(?ModuleHandlerInterface $handler = NULL, ?ModuleInstallerInterface $installer = NULL): DeployCommands {
    return new DeployCommands(
      $handler ?? $this->createMock(ModuleHandlerInterface::class),
      $installer ?? $this->createMock(ModuleInstallerInterface::class),
    );
  }

  /**
   * Invokes a protected method on the given object.
   *
   * @param object $object
   *   The object to invoke the method on.
   * @param string $method
   *   The protected method name.
   * @param array $args
   *   The arguments to pass to the method.
   *
   * @return mixed
   *   The method return value.
   */
  protected function invoke(object $object, string $method, array $args = []): mixed {
    $reflection = new \ReflectionMethod($object, $method);

    return $reflection->invoke($object, ...$args);
  }

}
