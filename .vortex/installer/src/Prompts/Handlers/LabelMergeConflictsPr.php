<?php

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
  public function process():void  {
    // @todo Implement this.
  }

}
