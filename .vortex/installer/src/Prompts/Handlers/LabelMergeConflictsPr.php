<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

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
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    if (empty($this->response)) {
      @unlink($this->tmpDir . '/.github/workflows/label-merge-conflict.yml');
    }
  }

}
