<?php

namespace DrevOps\Installer\Prompts\Handlers;

class CodeProvider extends AbstractHandler {

  const GITHUB = 'github';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!file_exists($this->dstDir . '/.github')) {
      return self::GITHUB;
    }

    return $this->isInstalled() && file_exists($this->dstDir . '/.git') ? self::OTHER : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->response !== self::GITHUB) {
      @unlink($this->tmpDir . '/.github');
    }
  }

}
