<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;

class MachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Composer::getJsonValue('name', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    File::replaceContentAsync([
      'your_site' => $v,
      'your-site' => Converter::kebab($v),
      'YourSite' => Converter::pascal($v),
    ]);

    File::renameInDir($t, 'your_site', $v);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '🏷️ Site machine name';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'We will use this name for the project directory and in the code.';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. my_site';
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
    return fn(string $v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidate(): ?callable {
    return fn($v): ?string => Converter::machineExtended($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultForContext(array $responses): mixed {
    // Generate default from Name if available
    if (isset($responses[Name::id()]) && !empty($responses[Name::id()])) {
      return Converter::machineExtended($responses[Name::id()]);
    }
    
    return $this->getDefault();
  }

}
