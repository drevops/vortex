<?php

declare(strict_types=1);

namespace Drupal\Tests\persistent_deploy\Unit;

use Drupal\persistent_deploy\PersistentDeployInterface;
use Drupal\persistent_deploy\PersistentDeployManager;
use Drupal\persistent_deploy\PersistentDeployRunner;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;

/**
 * Tests the PersistentDeployRunner.
 *
 * @package Drupal\persistent_deploy\Tests
 */
#[Group('PersistentDeploy')]
class PersistentDeployRunnerTest extends UnitTestCase {

  /**
   * Tests that open steps run and gated steps are skipped.
   */
  public function testRunsOpenStepsAndSkipsGatedSteps(): void {
    $open = $this->createMock(PersistentDeployInterface::class);
    $open->method('gate')->willReturn(NULL);
    $open->method('label')->willReturn('open step');
    $open->expects($this->once())->method('run');

    $gated = $this->createMock(PersistentDeployInterface::class);
    $gated->method('gate')->willReturn('production environment');
    $gated->method('label')->willReturn('gated step');
    $gated->expects($this->never())->method('run');

    $manager = $this->createMock(PersistentDeployManager::class);
    $manager->method('getSortedSteps')->willReturn(['open' => $open, 'gated' => $gated]);

    $runner = new PersistentDeployRunner($manager, $this->createMock(LoggerInterface::class));
    $runner->run(PersistentDeployInterface::PHASE_POST);
  }

  /**
   * Tests that a failing step aborts the run by propagating the exception.
   */
  public function testStepFailureAborts(): void {
    $failing = $this->createMock(PersistentDeployInterface::class);
    $failing->method('gate')->willReturn(NULL);
    $failing->method('label')->willReturn('failing step');
    $failing->method('run')->willThrowException(new \RuntimeException('Step failed.'));

    $manager = $this->createMock(PersistentDeployManager::class);
    $manager->method('getSortedSteps')->willReturn(['failing' => $failing]);

    $runner = new PersistentDeployRunner($manager, $this->createMock(LoggerInterface::class));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Step failed.');

    $runner->run(PersistentDeployInterface::PHASE_POST);
  }

  /**
   * Tests that a NULL phase runs every phase in order (pre, then post).
   */
  public function testRunsAllPhasesWhenNoPhaseGiven(): void {
    $pre = $this->createMock(PersistentDeployInterface::class);
    $pre->method('gate')->willReturn(NULL);
    $pre->method('label')->willReturn('pre step');
    $pre->expects($this->once())->method('run');

    $post = $this->createMock(PersistentDeployInterface::class);
    $post->method('gate')->willReturn(NULL);
    $post->method('label')->willReturn('post step');
    $post->expects($this->once())->method('run');

    $manager = $this->createMock(PersistentDeployManager::class);
    $manager->method('getSortedSteps')->willReturnMap([
      [PersistentDeployInterface::PHASE_PRE, ['pre' => $pre]],
      [PersistentDeployInterface::PHASE_POST, ['post' => $post]],
    ]);

    $runner = new PersistentDeployRunner($manager, $this->createMock(LoggerInterface::class));
    $runner->run();
  }

}
