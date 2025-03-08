<?php

namespace DrevOps\Installer\Prompts\Handlers;

class AssignAuthorPr extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? file_exists($this->dstDir . '/.github/workflows/assign-author.yml') : NULL;
  }

  public function process(): void {
    // @todo Implement this.
  }

}
