<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

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
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    if ($this->response !== self::GITHUB) {
      File::rmdir($this->tmpDir . '/.github');
    }
  }

}
