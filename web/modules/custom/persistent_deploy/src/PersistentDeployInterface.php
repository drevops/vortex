<?php

declare(strict_types=1);

namespace Drupal\persistent_deploy;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for persistent deploy plugins.
 *
 * A persistent deploy plugin is a unit of idempotent, repeatable work that runs
 * on every `drush deploy:hook`, in every environment. The runner groups plugins
 * by phase (pre/post), orders each phase by weight, calls ::gate() to decide
 * whether each plugin runs, and calls ::run() for the ones whose gate is open.
 *
 * This is the repeatable counterpart to run-once hook_deploy_NAME(): a plugin
 * runs on every deploy, so it must be idempotent.
 */
interface PersistentDeployInterface extends PluginInspectionInterface {

  /**
   * Phase that runs before the `deploy:hook` command body.
   */
  public const PHASE_PRE = 'pre';

  /**
   * Phase that runs after the `deploy:hook` command body.
   */
  public const PHASE_POST = 'post';

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
   *   Run order within the phase; steps run in ascending weight (lower first).
   */
  public function getWeight(): int;

  /**
   * Returns the deploy phase this step runs in.
   *
   * @return string
   *   One of self::PHASE_PRE (before the deploy hook body) or self::PHASE_POST
   *   (after it).
   */
  public function getPhase(): string;

  /**
   * Returns the human-readable step label used in deploy logs.
   *
   * @return string
   *   The label, falling back to the plugin ID.
   */
  public function label(): string;

}
