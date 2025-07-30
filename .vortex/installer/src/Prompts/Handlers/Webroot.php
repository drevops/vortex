<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\ComposerJson;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Validator;

class Webroot extends AbstractHandler {

  const WEB = 'web';

  const DOCROOT = 'docroot';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '📁 Custom web root directory';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Custom directory where the web server serves the site.';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. ' . implode(', ', [self::WEB, self::DOCROOT]);
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
    // Auto-select webroot based on hosting provider.
    if (isset($responses[HostingProvider::id()])) {
      return match ($responses[HostingProvider::id()]) {
        HostingProvider::ACQUIA => self::DOCROOT,
        HostingProvider::LAGOON => self::WEB,
        default => self::WEB,
      };
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $v1 = Env::getFromDotenv('WEBROOT', $this->dstDir);
    if (!empty($v1)) {
      return $v1;
    }

    $v2 = ComposerJson::fromFile($this->dstDir . '/composer.json')?->getProperty('extra.drupal-scaffold.locations.web-root');

    if (!empty($v2)) {
      return $v2;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Validator::dirname($v) ? NULL : 'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return fn(string $v): string => rtrim($v, DIRECTORY_SEPARATOR);
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedValue(array $responses): null|string|bool|array {
    return $this->discover();
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedMessage(array $responses): ?string {
    if (
      isset($responses[HostingProvider::id()]) &&
      $responses[HostingProvider::id()] !== HostingProvider::OTHER
    ) {
      $webroot = $this->default($responses);
      if (is_array($webroot)) {
        throw new \InvalidArgumentException('Web root must be a string, got: ' . gettype($webroot));
      }
      return sprintf('Web root will be set to "%s".', (string) $webroot);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $webroot = self::WEB;

    if ($v === $webroot) {
      return;
    }

    File::replaceContentAsync([
      sprintf('%s/', $webroot) => $v . '/',
      sprintf('%s\/', $webroot) => $v . '\/',
      sprintf(': %s', $webroot) => ': ' . $v,
      sprintf('=%s', $webroot) => '=' . $v,
      sprintf('!%s', $webroot) => '!' . $v,
      sprintf('/\/%s\//', $webroot) => '/' . $v . '/',
      sprintf('/\'\/%s\'/', $webroot) => "'/" . $v . "'",
    ]);

    rename($t . DIRECTORY_SEPARATOR . $webroot, $t . DIRECTORY_SEPARATOR . $v);
  }

}
