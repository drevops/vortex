<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class ProvisionType extends AbstractHandler {

  const DATABASE = 'database';

  const PROFILE = 'profile';

  public function discover(): null|string|bool|iterable {
    // @todo: Rename to VORTEX_PROVISION_TYPE
    return Env::getFromDotenv('VORTEX_PROVISION_USE_PROFILE', $this->dstDir) ? static::PROFILE : static::DATABASE;
  }

  public function process(): void {
    if ($this->response === static::PROFILE) {
      File::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=1", $this->tmpDir . '/.env');
      File::removeTokenWithContent('!PROVISION_USE_PROFILE', $this->tmpDir);
    }
    else {
      File::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=0", $this->tmpDir . '/.env');
      File::removeTokenWithContent('PROVISION_USE_PROFILE', $this->tmpDir);
    }
  }

}
