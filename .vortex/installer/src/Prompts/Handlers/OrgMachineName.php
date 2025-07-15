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
  public function label(): string {
    return '🏢 Organization machine name';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'We will use this name for the project directory and in the code.';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. my_org';
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
    if (isset($responses[Org::id()]) && !empty($responses[Org::id()])) {
      return Converter::machineExtended($responses[Org::id()]);
    }

    return NULL;
  }

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
  public function validate(): ?callable {
    return fn($v): ?string => Converter::machineExtended($v) !== $v ? 'Please enter a valid organisation machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL;
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

    File::replaceContentAsync('your_org', $v);
    File::renameInDir($this->tmpDir, 'your_org', $v);
  }

}
