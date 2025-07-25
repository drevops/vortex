<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;

class Name extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '🏷️ Site name';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'We will use this name in the project and in the documentation.';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. My Site';
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
    // Discover the name from the project directory.
    return Converter::label(basename((string) $this->config->getDst()));
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = NULL;

    $description = Composer::getJsonValue('description', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');
    if ($description && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+)(\s?\.|for)/', (string) $description, $matches) && !empty($matches[1])) {
      $value = trim($matches[1]);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Converter::label($v) !== $v ? 'Please enter a valid project name.' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return fn(string $v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    File::replaceContentAsync('YOURSITE', $v);
  }

}
