<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Validator;

class Domain extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $origin = Env::getFromDotenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN', $this->dstDir);

    if ($origin) {
      return Converter::domain($origin);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    File::replaceContentAsync('your-site-domain.example', $v);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '🌐 Public domain';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'Domain name without protocol and trailing slash.';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequired(): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransform(): ?callable {
    return fn(string $v): string => Converter::domain($v);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidate(): ?callable {
    return fn($v): ?string => Validator::domain($v) ? null : 'Please enter a valid domain name.';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultForContext(array $responses): mixed {
    // Generate default from MachineName if available
    if (isset($responses[MachineName::id()]) && !empty($responses[MachineName::id()])) {
      return Converter::kebab($responses[MachineName::id()]) . '.com';
    }
    
    return $this->getDefault();
  }

}
