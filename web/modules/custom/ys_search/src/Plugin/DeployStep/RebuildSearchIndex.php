<?php

declare(strict_types=1);

namespace Drupal\ys_search\Plugin\DeployStep;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DrushTrait;
use Drupal\deploy_steps\EnvironmentTrait;

/**
 * Rebuilds the search index on development deploys.
 *
 * Development environments (local, ci, dev, stage) receive a database whose
 * Search API tracker state does not match their own empty search backend, so
 * the tracker is reset and the content re-indexed. Other environments keep
 * their existing index. Runs in the POST phase, once the content is in place.
 * Idempotent - safe on every deploy.
 *
 * @codeCoverageIgnore
 */
#[DeployStep(
  id: 'ys_search_rebuild_index',
  label: new TranslatableMarkup('Search index rebuild'),
  weight: 10,
  phase: DeployStepInterface::PHASE_POST,
)]
final class RebuildSearchIndex extends DeployStepBase {

  use DrushTrait;
  use EnvironmentTrait;

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    if (getenv('DRUPAL_SEARCH_INDEX_SKIP') === '1') {
      return 'search indexing skipped via DRUPAL_SEARCH_INDEX_SKIP';
    }

    return in_array($this->environment(), ['local', 'ci', 'dev', 'stage'], TRUE) ? NULL : 'non-development environment';
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->drush('search-api:reset-tracker');
    $this->drush('search-api:index');
  }

}
