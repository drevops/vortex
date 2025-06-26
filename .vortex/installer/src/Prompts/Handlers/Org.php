<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\File;

class Org extends AbstractHandler {

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
  public function process(): void {
    $v = $this->getResponseAsString();

    File::replaceContentAsync('YOURORG', $v);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '🏢 Organization name';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(): ?string {
    return 'We will use this name in the project and in the documentation.';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(): ?string {
    return 'E.g. My Org';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return true;
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
  public function validate(): ?callable {
    return fn($v): ?string => \DrevOps\VortexInstaller\Utils\Converter::label($v) !== $v ? 'Please enter a valid organization name.' : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultForContext(array $responses): mixed {
    // Generate default from Name if available
    if (isset($responses[Name::id()]) && !empty($responses[Name::id()])) {
      return \DrevOps\VortexInstaller\Utils\Converter::label($responses[Name::id()]) . ' Org';
    }

    return $this->default();
  }

}
