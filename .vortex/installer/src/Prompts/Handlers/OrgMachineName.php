<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\File;

class OrgMachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Composer::getJsonValue('name', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    File::replaceContentAsync('your_org', $v);
    File::renameInDir($this->tmpDir, 'your_org', $v);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '🏢 Organization machine name';
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
    return 'E.g. my_org';
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
    return fn($v): ?string => Converter::machineExtended($v) !== $v ? 'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.' : null;
  }

}
