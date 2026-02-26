<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class AssignAuthorPr extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Auto-assign the author to their PR?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Helps to keep the PRs organized.';
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
    return $this->isInstalled() ? file_exists($this->dstDir . '/.github/workflows/assign-author.yml') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsBool();
    $t = $this->tmpDir;

    if (!$v) {
      File::remove($t . '/.github/workflows/assign-author.yml');
    }
  }

}
