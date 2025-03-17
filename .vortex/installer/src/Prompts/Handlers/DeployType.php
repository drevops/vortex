<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

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
    if (!is_array($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    $types = $this->response;
    $t = $this->tmpDir;

    if (!is_array($types)) {
      throw new \InvalidArgumentException('Invalid deploy types.');
    }

    if (!empty($types)) {
      File::replaceContent($t . '/.env', '/VORTEX_DEPLOY_TYPES=.*/', 'VORTEX_DEPLOY_TYPES=' . Converter::toList($types));

      if (!in_array(self::ARTIFACT, $types)) {
        @unlink($t . '/.gitignore.deployment');
        @unlink($t . '/.gitignore.artifact');
      }

      File::removeTokenInDir($t, '!DEPLOYMENT');
    }
    else {
      @unlink($t . '/docs/deployment.md');
      @unlink($t . '/.gitignore.deployment');
      @unlink($t . '/.gitignore.artifact');
      File::removeTokenInDir($t, 'DEPLOYMENT');
    }
  }

}
