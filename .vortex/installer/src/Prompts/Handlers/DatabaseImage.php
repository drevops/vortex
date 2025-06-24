<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Validator;

class DatabaseImage extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('VORTEX_DB_IMAGE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!empty($this->response)) {
      $v = $this->getResponseAsString();

      File::replaceContentInFile(
        $this->tmpDir . '/.env', '/# VORTEX_DB_IMAGE=.*/',
        'VORTEX_DB_IMAGE=' . $v
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '🏷️ What is your database container image name and a tag?';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'Use "latest" tag for the latest version. CI will be building this image overnight.';
  }

  /**
   * {@inheritdoc}
   */
  public function getTransform(): ?callable {
    return fn($v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidate(): ?callable {
    return fn($v): ?string => Validator::containerImage($v) ? null : 'Please enter a valid container image name with an optional tag.';
  }

  /**
   * {@inheritdoc}
   */
  public function isConditional(): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function getCondition(): ?callable {
    return fn(array $responses): bool => $responses[DatabaseDownloadSource::id()] === DatabaseDownloadSource::CONTAINER_REGISTRY;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    // This will be dynamically set based on responses in PromptManager
    return null;
  }

}
