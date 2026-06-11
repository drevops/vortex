<?php

declare(strict_types=1);

namespace Drupal\persistent_deploy\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\persistent_deploy\PersistentDeployInterface;

/**
 * Defines a persistent deploy plugin.
 *
 * A persistent deploy plugin is a unit of idempotent work that runs on every
 * `drush deploy:hook` - the repeatable counterpart to run-once
 * hook_deploy_NAME(). Place the plugin class in any enabled module's
 * `Plugin/PersistentDeploy/` namespace; the persistent_deploy runner discovers
 * it, orders it by weight within its phase, asks its gate whether to run, and
 * runs it.
 *
 * @code
 * #[PersistentDeploy(
 *   id: 'rebuild_search_index',
 *   label: new TranslatableMarkup('Rebuild search index'),
 *   weight: 10,
 *   phase: PersistentDeployInterface::PHASE_POST,
 * )]
 * final class RebuildSearchIndex extends PersistentDeployBase {
 *   public function run(): void {
 *     // ...
 *   }
 * }
 * @endcode
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class PersistentDeploy extends AttributeBase {

  /**
   * Constructs a PersistentDeploy attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string|null $label
   *   A human-readable label, shown in deploy logs.
   * @param int $weight
   *   Run order within the phase; steps run in ascending weight (lower first).
   * @param string $phase
   *   The deploy phase: PersistentDeployInterface::PHASE_PRE (before the deploy
   *   hook body) or ::PHASE_POST (after it, the default).
   */
  public function __construct(
    string $id,
    public readonly mixed $label = NULL,
    public readonly int $weight = 0,
    public readonly string $phase = PersistentDeployInterface::PHASE_POST,
  ) {
    parent::__construct($id);
  }

}
