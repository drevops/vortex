<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class ProvisionType extends AbstractHandler {

  const DATABASE = 'database';

  const PROFILE = 'profile';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $type = Env::getFromDotenv('VORTEX_PROVISION_TYPE', $this->dstDir);

    return $type && in_array($type, [self::DATABASE, self::PROFILE]) ? $type : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->response === static::PROFILE) {
      File::fileReplaceContent('/VORTEX_PROVISION_TYPE=.*/', "VORTEX_PROVISION_TYPE=" . static::PROFILE, $this->tmpDir . '/.env');
      File::removeTokenWithContent('!PROVISION_USE_PROFILE', $this->tmpDir);
    }
    else {
      File::fileReplaceContent('/VORTEX_PROVISION_TYPE=.*/', "VORTEX_PROVISION_TYPE=" . static::DATABASE, $this->tmpDir . '/.env');
      File::removeTokenWithContent('PROVISION_USE_PROFILE', $this->tmpDir);
    }
  }

}
