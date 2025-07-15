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
  public function label(): string {
    return '🌐 Public domain';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Domain name without protocol and trailing slash.';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    if (isset($responses[MachineName::id()]) && !empty($responses[MachineName::id()])) {
      return Converter::kebab($responses[MachineName::id()]) . '.com';
    }

    return NULL;
  }

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
  public function validate(): ?callable {
    return fn($v): ?string => Validator::domain($v) ? NULL : 'Please enter a valid domain name.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return fn(string $v): string => Converter::domain($v);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    File::replaceContentAsync('your-site-domain.example', $v);
  }

}
