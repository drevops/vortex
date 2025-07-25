<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class MachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '🏷️ Site machine name';
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
    return 'E.g. my_site';
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
      return Converter::machineExtended($responses[Name::id()]);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = NULL;

    $from_env = Env::getFromDotenv('VORTEX_PROJECT', $this->dstDir);
    if ($from_env) {
      $value = $from_env;
    }
    else {
      $from_composerjson = Composer::getJsonValue('name', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');
      if ($from_composerjson && preg_match('/([^\/]+)\/(.+)/', (string) $from_composerjson, $matches) && !empty($matches[2])) {
        $value = $matches[2];
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Converter::machineExtended($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL;
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
    $t = $this->tmpDir;

    File::replaceContentAsync([
      'your_site' => $v,
      'your-site' => Converter::kebab($v),
      'YourSite' => Converter::pascal($v),
    ]);

    File::renameInDir($t, 'your_site', $v);
  }

}
