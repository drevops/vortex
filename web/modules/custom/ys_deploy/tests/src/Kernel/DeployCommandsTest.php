<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_deploy\Kernel;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ys_deploy\Drush\Commands\DeployCommands;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests DeployCommands against a real site.
 *
 * @package Drupal\ys_deploy\Tests
 */
#[Group('YsDeploy')]
class DeployCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ys_deploy'];

  /**
   * Tests that the command class resolves its services from the container.
   */
  public function testCreateResolvesServices(): void {
    $commands = DeployCommands::create($this->container);

    $this->assertInstanceOf(DeployCommands::class, $commands);
  }

  /**
   * Tests that the pre- and post-deploy hooks run against a real site.
   */
  public function testDeployHooksRun(): void {
    $this->setSetting('environment', 'ci');

    $commands = DeployCommands::create($this->container);
    $command_data = $this->createMock(CommandData::class);

    // Both hooks run their step sequences without error. The shipped
    // post-deploy step reads the environment from real settings.
    $commands->preDeploy($command_data);
    $commands->postDeploy(NULL, $command_data);

    $reflection = new \ReflectionMethod($commands, 'environment');
    $this->assertSame('ci', $reflection->invoke($commands));
  }

}
