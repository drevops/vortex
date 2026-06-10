<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_deploy\Unit;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Tests\UnitTestCase;
use Drupal\ys_deploy\DeployStepInterface;
use Drupal\ys_deploy\DeployStepManager;
use Drupal\ys_deploy\Drush\Commands\DeployCommands;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DeployCommands runner.
 *
 * @package Drupal\ys_deploy\Tests
 */
#[Group('YsDeploy')]
class DeployCommandsTest extends UnitTestCase {

  /**
   * Tests that open steps run and gated steps are skipped.
   */
  public function testRunsOpenStepsAndSkipsGatedSteps(): void {
    $open = $this->createMock(DeployStepInterface::class);
    $open->method('gate')->willReturn(NULL);
    $open->method('label')->willReturn('open step');
    $open->expects($this->once())->method('run');

    $gated = $this->createMock(DeployStepInterface::class);
    $gated->method('gate')->willReturn('production environment');
    $gated->method('label')->willReturn('gated step');
    $gated->expects($this->never())->method('run');

    $manager = $this->createMock(DeployStepManager::class);
    $manager->method('getSortedSteps')->willReturn(['open' => $open, 'gated' => $gated]);

    $commands = new DeployCommands($manager);
    $commands->runDeploySteps(NULL, $this->createMock(CommandData::class));
  }

  /**
   * Tests that a failing step aborts the run by propagating the exception.
   */
  public function testStepFailureAborts(): void {
    $failing = $this->createMock(DeployStepInterface::class);
    $failing->method('gate')->willReturn(NULL);
    $failing->method('label')->willReturn('failing step');
    $failing->method('run')->willThrowException(new \RuntimeException('Step failed.'));

    $manager = $this->createMock(DeployStepManager::class);
    $manager->method('getSortedSteps')->willReturn(['failing' => $failing]);

    $commands = new DeployCommands($manager);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Step failed.');

    $commands->runDeploySteps(NULL, $this->createMock(CommandData::class));
  }

}
