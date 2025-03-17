<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

class AssignAuthorPr extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? file_exists($this->dstDir . '/.github/workflows/assign-author.yml') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (empty($this->response)) {
      @unlink($this->tmpDir . '/.github/workflows/assign-author.yml');
    }
  }

}
