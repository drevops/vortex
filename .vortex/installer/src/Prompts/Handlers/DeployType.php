<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class DeployType extends AbstractHandler {

  const ARTIFACT = 'artifact';

  const LAGOON = 'lagoon';

  const CONTAINER_IMAGE = 'container_image';

  const WEBHOOK = 'webhook';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Deployment types';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select one or more deployment types.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    $options = [
      self::ARTIFACT => 'Code artifact',
      self::LAGOON => 'Lagoon webhook',
      self::CONTAINER_IMAGE => 'Container image',
      self::WEBHOOK => 'Custom webhook',
    ];

    // Remove Lagoon option for Acquia hosting.
    if (isset($responses[HostingProvider::id()]) && $responses[HostingProvider::id()] === HostingProvider::ACQUIA) {
      unset($options[self::LAGOON]);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    $defaults = [];

    if (isset($responses[HostingProvider::id()])) {
      if ($responses[HostingProvider::id()] === HostingProvider::LAGOON) {
        $defaults[] = self::LAGOON;
      }

      if ($responses[HostingProvider::id()] === HostingProvider::ACQUIA) {
        $defaults[] = self::ARTIFACT;
      }
    }

    if (empty($defaults)) {
      $defaults[] = self::WEBHOOK;
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $types = Env::getFromDotenv('VORTEX_DEPLOY_TYPES', $this->dstDir);

    if (!empty($types)) {
      $types = Converter::fromList($types);
      sort($types);
      return $types;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $types = $this->getResponseAsArray();
    $t = $this->tmpDir;

    if (!empty($types)) {
      File::replaceContentInFile(
        $t . '/.env', '/VORTEX_DEPLOY_TYPES=.*/',
        'VORTEX_DEPLOY_TYPES=' . Converter::toList($types)
      );

      if (!in_array(self::ARTIFACT, $types)) {
        @unlink($t . '/.gitignore.deployment');
        @unlink($t . '/.gitignore.artifact');
      }

      File::removeTokenAsync('!DEPLOYMENT');
    }
    else {
      @unlink($t . '/docs/deployment.md');
      @unlink($t . '/.gitignore.deployment');
      @unlink($t . '/.gitignore.artifact');

      File::removeTokenAsync('DEPLOYMENT');
    }
  }

}
