<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

class LabelMergeConflictsPr extends AbstractHandler {

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

    if (empty($v)) {
      @unlink($this->tmpDir . '/.github/workflows/label-merge-conflict.yml');
    }
  }

}
