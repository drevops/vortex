<?php

declare(strict_types=1);

namespace Drupal\Tests\persistent_deploy\Kernel;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\KernelTests\KernelTestBase;
use Drupal\persistent_deploy\Drush\Commands\PersistentDeployCommands;
use Drupal\persistent_deploy\PersistentDeployInterface;
use Drupal\persistent_deploy\PersistentDeployManager;
use Drupal\persistent_deploy\PersistentDeployRunner;
use Drupal\persistent_deploy\Plugin\PersistentDeploy\RecordEnvironment;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests persistent deploy discovery, the runner, and the command on a site.
 *
 * @package Drupal\persistent_deploy\Tests
 */
#[Group('PersistentDeploy')]
class PersistentDeployCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['persistent_deploy'];

  /**
   * Tests that the manager discovers plugins from enabled modules.
   */
  public function testManagerDiscoversSteps(): void {
    $manager = $this->container->get('plugin.manager.persistent_deploy');
    $this->assertInstanceOf(PersistentDeployManager::class, $manager);

    $steps = $manager->getSortedSteps(PersistentDeployInterface::PHASE_POST);
    $this->assertArrayHasKey('record_environment', $steps);
  }

  /**
   * Tests that the runner executes discovered steps against a real site.
   */
  public function testRunnerExecutesSteps(): void {
    $this->setSetting('environment', 'ci');

    $this->container->get(PersistentDeployRunner::class)->run();

    $this->assertSame('ci', $this->container->get('state')->get(RecordEnvironment::STATE_KEY));
  }

  /**
   * Tests that the command's post hook runs the discovered steps.
   */
  public function testCommandRunsPostPhase(): void {
    $this->setSetting('environment', 'ci');

    $commands = PersistentDeployCommands::create($this->container);
    $commands->runPostDeploySteps(NULL, $this->createMock(CommandData::class));

    $this->assertSame('ci', $this->container->get('state')->get(RecordEnvironment::STATE_KEY));
  }

}
