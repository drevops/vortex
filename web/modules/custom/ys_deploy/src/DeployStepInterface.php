<?php

declare(strict_types=1);

namespace Drupal\ys_deploy;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for deploy step plugins.
 *
 * A deploy step is a unit of idempotent, repeatable work that runs on every
 * `drush deploy:hook`, in every environment. The runner orders steps by weight,
 * calls ::gate() to decide whether each step runs, and calls ::run() for the
 * steps whose gate is open.
 *
 * This is the repeatable counterpart to run-once hook_deploy_NAME(): a step runs
 * on every deploy, so it must be idempotent.
 */
interface DeployStepInterface extends PluginInspectionInterface {

  /**
   * Decides whether this step should run on the current deploy.
   *
   * The gate is where a step expresses its conditions - typically the
   * environment, a feature flag, or the presence of data. Returning a reason
   * (rather than a bare boolean) means every skip is explicit and explained in
   * the deploy log instead of silently vanishing.
   *
   * @return string|null
   *   NULL to run the step, or a short human-readable reason to skip it (logged
   *   verbatim, e.g. "production environment" or "migration source DB absent").
   */
  public function gate(): ?string;

  /**
   * Runs the step.
   *
   * Must be idempotent - it runs on every deploy. Throw to abort the deploy
   * loudly rather than continue silently.
   */
  public function run(): void;

  /**
   * Returns the step weight.
   *
   * @return int
   *   Run order; steps run in ascending weight (lower runs first).
   */
  public function getWeight(): int;

  /**
   * Returns the human-readable step label used in deploy logs.
   *
   * @return string
   *   The label, falling back to the plugin ID.
   */
  public function label(): string;

}
