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

  public function discover(): ?string {
    return Env::getFromDotenv('VORTEX_DEPLOY_TYPES', $this->dstDir);
  }

  public function process(): void {
    $type = $this->getAnswer('deploy_type');

    if ($type !== 'none') {
      File::fileReplaceContent('/VORTEX_DEPLOY_TYPES=.*/', 'VORTEX_DEPLOY_TYPES=' . $type, $dir . '/.env');

      if (!str_contains($type, 'artifact')) {
        @unlink($dir . '/.gitignore.deployment');
        @unlink($dir . '/.gitignore.artifact');
      }

      File::removeTokenWithContent('!DEPLOYMENT', $dir);
    }
    else {
      @unlink($dir . '/docs/deployment.md');
      @unlink($dir . '/.gitignore.deployment');
      @unlink($dir . '/.gitignore.artifact');
      File::removeTokenWithContent('DEPLOYMENT', $dir);
    }
  }

}
