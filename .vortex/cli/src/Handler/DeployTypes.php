<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

class DeployTypes extends AbstractHandler {

  const ARTIFACT = 'artifact';

  const LAGOON = 'lagoon';

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

    if (!in_array(self::ARTIFACT, $types)) {
      File::removeTokenAsync('DEPLOY_TYPES_ARTIFACT');
      File::remove($t . '/.gitignore.deployment');
      File::remove($t . '/.gitignore.artifact');
    }

    if (!in_array(self::WEBHOOK, $types)) {
      File::removeTokenAsync('DEPLOY_TYPES_WEBHOOK');
    }

    if (!empty($types)) {
      Env::writeValueDotenv('VORTEX_DEPLOY_TYPES', Converter::toList($types), $t . '/.env');

      File::removeTokenAsync('!DEPLOYMENT');
    }
    else {
      File::remove($t . '/docs/deployment.md');

      File::removeTokenAsync('DEPLOYMENT');
    }
  }

}
