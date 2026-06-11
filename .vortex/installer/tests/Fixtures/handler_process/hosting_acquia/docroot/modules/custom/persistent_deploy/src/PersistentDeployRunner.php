<?php

declare(strict_types=1);

namespace Drupal\persistent_deploy;

use Psr\Log\LoggerInterface;

/**
 * Runs persistent deploy step plugins.
 *
 * The Drush command hooks on `deploy:hook` call this with a single phase each
 * (pre, then post), so the same gate and logging behaviour applies to every
 * step on every deploy.
 */
class PersistentDeployRunner {

  /**
   * Constructs a PersistentDeployRunner object.
   *
   * @param \Drupal\persistent_deploy\PersistentDeployManager $manager
   *   The persistent deploy plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected readonly PersistentDeployManager $manager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Runs the deploy steps for one phase, or for all phases.
   *
   * @param string|null $phase
   *   A single phase (PersistentDeployInterface::PHASE_PRE or ::PHASE_POST), or
   *   NULL to run every phase in order (pre, then post).
   */
  public function run(?string $phase = NULL): void {
    $phases = $phase !== NULL ? [$phase] : [PersistentDeployInterface::PHASE_PRE, PersistentDeployInterface::PHASE_POST];

    foreach ($phases as $current_phase) {
      $this->runPhase($current_phase);
    }
  }

  /**
   * Runs all open deploy steps for a phase, in weight order.
   *
   * @param string $phase
   *   The phase to run.
   */
  protected function runPhase(string $phase): void {
    foreach ($this->manager->getSortedSteps($phase) as $step) {
      $skip_reason = $step->gate();

      if ($skip_reason !== NULL) {
        $this->logger->notice('Skipped deploy step "@label": @reason', [
          '@label' => $step->label(),
          '@reason' => $skip_reason,
        ]);

        continue;
      }

      $this->logger->notice('Running deploy step "@label".', ['@label' => $step->label()]);
      $step->run();
    }
  }

}
