<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class PreserveDocsOnboarding extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '📋 Preserve onboarding checklist?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Helps to track onboarding to Vortex within the repository.';
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
    return $this->isInstalled() ? file_exists($this->dstDir . '/docs/onboarding.md') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    if (!empty($v)) {
      File::removeTokenAsync('!DOCS_ONBOARDING');
    }
    else {
      @unlink($this->tmpDir . '/docs/onboarding.md');
      File::removeTokenAsync('DOCS_ONBOARDING');
    }
  }

}
