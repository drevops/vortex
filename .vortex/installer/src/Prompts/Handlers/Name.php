<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class Name extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Composer::getJsonValue('description', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    File::replaceContentAsync('YOURSITE', $v);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '🏷️ Site name';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'We will use this name in the project and in the documentation.';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. My Site';
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
  public function getDefault(): mixed {
    return $this->discover() ?? Converter::label(Env::get('VORTEX_PROJECT', basename((string) $this->config->getDst())));
  }

  /**
   * {@inheritdoc}
   */
  public function getTransform(): ?callable {
    return fn(string $v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidate(): ?callable {
    return fn($v): ?string => Converter::label($v) !== $v ? 'Please enter a valid project name.' : null;
  }

}
