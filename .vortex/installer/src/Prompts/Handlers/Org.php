<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;

class Org extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '🏢 Organization name';
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
    return 'E.g. My Org';
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
    if (isset($responses[Name::id()]) && !empty($responses[Name::id()])) {
      return Converter::label($responses[Name::id()]) . ' Org';
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Composer::getJsonValue('description', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Converter::label($v) !== $v ? 'Please enter a valid organization name.' : NULL;
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

    File::replaceContentAsync('YOURORG', $v);
  }

}
