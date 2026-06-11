<?php

declare(strict_types=1);

namespace Drupal\Tests\persistent_deploy\Unit;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\persistent_deploy\Drush\Commands\PersistentDeployCommands;
use Drupal\persistent_deploy\PersistentDeployInterface;
use Drupal\persistent_deploy\PersistentDeployRunner;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the PersistentDeployCommands runner hooks.
 *
 * @package Drupal\persistent_deploy\Tests
 */
#[Group('PersistentDeploy')]
class PersistentDeployCommandsTest extends UnitTestCase {

  /**
   * Tests that the pre-command hook runs the PRE phase.
   */
  public function testPreHookRunsPrePhase(): void {
    $runner = $this->createMock(PersistentDeployRunner::class);
    $runner->expects($this->once())->method('run')->with(PersistentDeployInterface::PHASE_PRE);

    $commands = new PersistentDeployCommands($runner);
    $commands->runPreDeploySteps($this->createMock(CommandData::class));
  }

  /**
   * Tests that the post-command hook runs the POST phase.
   */
  public function testPostHookRunsPostPhase(): void {
    $runner = $this->createMock(PersistentDeployRunner::class);
    $runner->expects($this->once())->method('run')->with(PersistentDeployInterface::PHASE_POST);

    $commands = new PersistentDeployCommands($runner);
    $commands->runPostDeploySteps(NULL, $this->createMock(CommandData::class));
  }

}
