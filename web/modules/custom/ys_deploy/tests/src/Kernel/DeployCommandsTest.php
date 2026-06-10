<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_deploy\Kernel;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ys_deploy\DeployStepManager;
use Drupal\ys_deploy\Drush\Commands\DeployCommands;
use Drupal\ys_deploy\Plugin\DeployStep\RecordEnvironment;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests deploy step discovery and the runner against a real site.
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
   * Tests that the manager discovers DeployStep plugins from enabled modules.
   */
  public function testManagerDiscoversSteps(): void {
    $manager = $this->container->get('plugin.manager.ys_deploy.deploy_step');
    $this->assertInstanceOf(DeployStepManager::class, $manager);

    $steps = $manager->getSortedSteps();
    $this->assertArrayHasKey('record_environment', $steps);
  }

  /**
   * Tests that the runner executes discovered steps against a real site.
   */
  public function testRunnerExecutesSteps(): void {
    $this->setSetting('environment', 'ci');

    $commands = DeployCommands::create($this->container);
    $commands->runDeploySteps(NULL, $this->createMock(CommandData::class));

    $this->assertSame('ci', $this->container->get('state')->get(RecordEnvironment::STATE_KEY));
  }

}
