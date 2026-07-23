<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\VortexCli\Utils\File;

/**
 * Handles the "label_merge_conflicts_pr" question.
 */
class LabelMergeConflictsPr extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Auto-add a CONFLICT label to a PR when conflicts occur?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Helps to keep quickly identify PRs that need attention.';
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? file_exists($this->dstDir . '/.github/workflows/label-merge-conflict.yml') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if (empty($v)) {
      File::remove($t . '/.github/workflows/label-merge-conflict.yml');
    }
  }

}
