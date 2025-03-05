<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class DeployType extends AbstractHandler {

  const NONE = 'none';

  const ARTIFACT = 'artifact';

  const LAGOON = 'lagoon';

  const CONTAINER_IMAGE = 'container_image';

  const WEBHOOK = 'webhook';

  public static function id(): string {
    return 'deploy_type';
  }

  public function discover(): null|string|bool|iterable {
    return Env::getFromDotenv('VORTEX_DEPLOY_TYPES', $this->dstDir);
  }

  public function process(): void {
    $type = $this->getAnswer('deploy_type');

    if ($type !== 'none') {
      File::fileReplaceContent('/VORTEX_DEPLOY_TYPES=.*/', 'VORTEX_DEPLOY_TYPES=' . $type, $this->tmpDir . '/.env');

      if (!str_contains($type, 'artifact')) {
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
