<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Composer;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Validator;

class Webroot extends AbstractHandler {

  const WEB = 'web';

  const DOCROOT = 'docroot';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Env::getFromDotenv('WEBROOT', $this->dstDir);

    if (empty($value)) {
      // Try from composer.json.
      $extra = Composer::getJsonValue('extra', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');
      if (!empty($extra)) {
        $value = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $value;
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

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '📁 Custom web root directory';
  }

  /**
   * {@inheritdoc}
   */
  public function getHint(): ?string {
    return 'Custom directory where the web server serves the site.';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder(): ?string {
    return 'E.g. ' . implode(', ', [self::WEB, self::DOCROOT]);
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
    return fn(string $v): string => rtrim($v, DIRECTORY_SEPARATOR);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidate(): ?callable {
    return fn($v): ?string => Validator::dirname($v) ? null : 'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault(): mixed {
    return $this->discover() ?? self::WEB;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultForContext(array $responses): mixed {
    // Auto-select webroot based on hosting provider
    if (isset($responses[HostingProvider::id()])) {
      $webroot = match ($responses[HostingProvider::id()]) {
        HostingProvider::ACQUIA => self::DOCROOT,
        HostingProvider::LAGOON => self::WEB,
        default => $this->getDefault()
      };
      return $webroot;
    }

    return $this->getDefault();
  }

  /**
   * Check if webroot should show as auto-selected info instead of input.
   */
  public function shouldShowAsInfo(array $responses): bool {
    return isset($responses[HostingProvider::id()]) &&
           $responses[HostingProvider::id()] !== HostingProvider::OTHER;
  }

  /**
   * Get the info message for auto-selected webroot.
   */
  public function getInfoMessage(array $responses): string {
    $webroot = $this->getDefaultForContext($responses);
    return sprintf('Web root will be set to "%s".', $webroot);
  }

  /**
   * {@inheritdoc}
   */
  public function isConditional(): bool {
    // Webroot has two modes: auto-select (info display) or text input
    return false; // Always shown, but behavior changes based on hosting provider
  }

  /**
   * Check if this prompt should use text input instead of auto-selection.
   */
  public function shouldUseTextInput(array $responses): bool {
    return isset($responses[HostingProvider::id()]) &&
           $responses[HostingProvider::id()] === HostingProvider::OTHER;
  }

  /**
   * Display info message and return the auto-selected webroot value.
   */
  public function showInfoAndReturnValue(array $responses): string {
    $webroot = $this->getDefaultForContext($responses);
    \Laravel\Prompts\info($this->getInfoMessage($responses));
    return $webroot;
  }

  /**
   * {@inheritdoc}
   */
  public function resolved(array $responses): null|string|bool|array {
    if ($this->shouldShowAsInfo($responses)) {
      return $this->getDefaultForContext($responses);
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function resolvedMessage(array $responses): ?string {
    if ($this->shouldShowAsInfo($responses)) {
      return $this->getInfoMessage($responses);
    }
    return null;
  }

}
