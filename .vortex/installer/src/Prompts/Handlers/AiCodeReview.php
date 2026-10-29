<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

/**
 * Configure AI code review with CodeRabbit.
 *
 * Ships a CodeRabbit configuration that limits review comments to issues that
 * matter and reviews against the conventions documented in the project. When
 * disabled, the configuration file is removed.
 */
class AiCodeReview extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 25;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Configure AI code review with CodeRabbit?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Requires the CodeRabbit app on the repository.';
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return File::exists($this->destinationDir . '/.coderabbit.yaml');
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsBool();
    $t = $this->tmpDir;

    if (!$v) {
      File::remove($t . '/.coderabbit.yaml');
    }
  }

}
