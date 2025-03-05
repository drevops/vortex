<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Util;
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
      return Converter::fromList($types);
    }

    return NULL;
  }

  public function process(): void {
    $types = $this->response;

    if (!is_array($types)) {
      throw new \InvalidArgumentException('Invalid deploy types.');
    }

    if (!empty($types)) {
      File::fileReplaceContent('/VORTEX_DEPLOY_TYPES=.*/', 'VORTEX_DEPLOY_TYPES=' . Converter::toList($types), $this->tmpDir . '/.env');

      if (!in_array(self::ARTIFACT, $types)) {
        @unlink($this->tmpDir . '/.gitignore.deployment');
        @unlink($this->tmpDir . '/.gitignore.artifact');
      }

      File::removeTokenWithContent('!DEPLOYMENT', $this->tmpDir);
    }
    else {
      @unlink($this->tmpDir . '/docs/deployment.md');
      @unlink($this->tmpDir . '/.gitignore.deployment');
      @unlink($this->tmpDir . '/.gitignore.artifact');
      File::removeTokenWithContent('DEPLOYMENT', $this->tmpDir);
    }
  }

}
