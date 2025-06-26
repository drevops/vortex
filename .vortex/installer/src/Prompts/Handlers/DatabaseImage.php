<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
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
  public function label(): string {
    return '🏷️ What is your database container image name and a tag?';
  }

  /**
   * {@inheritdoc}
   * @param array $responses
   */
  public function hint(array $responses): ?string {
    return 'Use "latest" tag for the latest version. CI will be building this image overnight.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return fn($v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Validator::containerImage($v) ? NULL : 'Please enter a valid container image name with an optional tag.';
  }

  /**
   * {@inheritdoc}
   */
  public function condition(): ?callable {
    return fn(array $responses): bool => $responses[DatabaseDownloadSource::id()] === DatabaseDownloadSource::CONTAINER_REGISTRY;
  }

  /**
   * {@inheritdoc}
   * @param mixed &$default
   */
  public function default(array $responses): mixed {
    if (
      isset($responses[OrgMachineName::id()]) &&
      isset($responses[MachineName::id()]) &&
      !empty($responses[OrgMachineName::id()]) &&
      !empty($responses[MachineName::id()])
    ) {
      return sprintf(
        '%s/%s-data:latest',
        Converter::phpNamespace($responses[OrgMachineName::id()]),
        Converter::phpNamespace($responses[MachineName::id()])
      );
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    // Generate placeholder from OrgMachineName and MachineName if available
    if (isset($responses[OrgMachineName::id()]) && isset($responses[MachineName::id()])
      && !empty($responses[OrgMachineName::id()]) && !empty($responses[MachineName::id()])) {
      return sprintf('E.g. %s/%s-data:latest',
        \DrevOps\VortexInstaller\Utils\Converter::phpNamespace($responses[OrgMachineName::id()]),
        \DrevOps\VortexInstaller\Utils\Converter::phpNamespace($responses[MachineName::id()])
      );
    }

    return $this->placeholder();
  }

}
