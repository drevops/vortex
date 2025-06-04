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
