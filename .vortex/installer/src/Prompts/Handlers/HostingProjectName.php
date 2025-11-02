<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class HostingProjectName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Hosting project name';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Name as found in the hosting configuration. Usually the same as the site machine name.';
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
  public function shouldRun(array $responses): bool {
    return isset($responses[HostingProvider::id()]) &&
      (
        $responses[HostingProvider::id()] === HostingProvider::LAGOON ||
        $responses[HostingProvider::id()] === HostingProvider::ACQUIA
      );
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    if (isset($responses[MachineName::id()]) && !empty($responses[MachineName::id()])) {
      return $responses[MachineName::id()];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    // Try Acquia.
    $v = Env::getFromDotenv('VORTEX_ACQUIA_APP_NAME', $this->dstDir);
    if (!empty($v)) {
      return $v;
    }

    // Try to discover from settings.acquia.php.
    $acquia_settings_file = $this->dstDir . sprintf('/%s/sites/default/includes/providers/settings.acquia.php', $this->webroot);
    if (file_exists($acquia_settings_file)) {
      $content = file_get_contents($acquia_settings_file);
      // Require '/var/www/site-php/your_site/your_site-settings.inc';.
      if ($content !== FALSE && preg_match('/require\s+[\'"]\/var\/www\/site-php\/([a-z0-9_]+)\/[a-z0-9_]+-settings\.inc[\'"]\s*;/', $content, $matches) && !empty($matches[1])) {
        return $matches[1];
      }
    }

    // Try Lagoon.
    $v = Env::getFromDotenv('LAGOON_PROJECT', $this->dstDir);
    if (!empty($v)) {
      return $v;
    }

    // Try to discover from drush/sites/lagoon.site.yml.
    $lagoon_site_file = $this->dstDir . '/drush/sites/lagoon.site.yml';
    if (file_exists($lagoon_site_file)) {
      $content = file_get_contents($lagoon_site_file);
      if ($content !== FALSE && preg_match('/user:\s*([a-z0-9_]+)-/', $content, $matches) && (!empty($matches[1]) && $matches[1] !== 'your_site')) {
        return $matches[1];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn($v): ?string => Converter::phpPackageName($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.' : NULL;
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
    if (!in_array($this->responses[HostingProvider::id()], [HostingProvider::LAGOON, HostingProvider::ACQUIA])) {
      return;
    }

    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $w = $this->webroot;

    Env::writeValueDotenv('VORTEX_ACQUIA_APP_NAME', $v, $t . '/.env');
    File::replaceContentInFile($t . '/' . $w . '/sites/default/includes/providers/settings.acquia.php', 'your_site', $v);

    Env::writeValueDotenv('LAGOON_PROJECT', $v, $t . '/.env');
    File::replaceContentInFile($t . '/drush/sites/lagoon.site.yml', 'your_site-${env-name}', $v . '-${env-name}');
    File::replaceContentInFile($t . '/drush/sites/lagoon.site.yml', '.your_site.au2.amazee.io', '.' . $v . '.au2.amazee.io');
  }

}
