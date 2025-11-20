<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class PreserveDocsProject extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Preserve project documentation?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Helps to maintain the project documentation within the repository.';
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
    return $this->isInstalled() ? file_exists($this->dstDir . '/docs/README.md') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if (!empty($v)) {
      File::removeTokenAsync('!DOCS_PROJECT');
    }
    else {
      File::rmdir($t . '/docs');
      File::removeTokenAsync('DOCS_PROJECT');
    }
  }

}
