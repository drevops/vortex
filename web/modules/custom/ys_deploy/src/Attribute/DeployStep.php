<?php

declare(strict_types=1);

namespace Drupal\ys_deploy\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;

/**
 * Defines a deploy step plugin.
 *
 * A deploy step is a unit of idempotent work that runs on every
 * `drush deploy:hook`. Place the plugin class in any enabled module's
 * `Plugin/DeployStep/` namespace; the ys_deploy runner discovers it, orders it
 * by weight, asks its gate whether to run, and runs it.
 *
 * @code
 * #[DeployStep(id: 'rebuild_search_index', label: new TranslatableMarkup('Rebuild search index'), weight: 10)]
 * final class RebuildSearchIndex extends DeployStepBase {
 *   public function run(): void {
 *     // ...
 *   }
 * }
 * @endcode
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class DeployStep extends AttributeBase {

  /**
   * Constructs a DeployStep attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string|null $label
   *   A human-readable label, shown in deploy logs.
   * @param int $weight
   *   Run order; steps run in ascending weight (lower runs first).
   */
  public function __construct(
    string $id,
    public readonly mixed $label = NULL,
    public readonly int $weight = 0,
  ) {
    parent::__construct($id);
  }

}
