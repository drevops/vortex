<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class ProvisionUseProfileHandler extends AbstractHandler {

  public function discover(): ?string {
    return (bool) Env::getFromDotenv('VORTEX_PROVISION_USE_PROFILE', $this->dstDir);
  }

  public function process(): void {
    if ($this->getAnswer('provision_use_profile')) {
      File::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=1", $dir . '/.env');
      File::removeTokenWithContent('!PROVISION_USE_PROFILE', $dir);
    }
    else {
      File::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=0", $dir . '/.env');
      File::removeTokenWithContent('PROVISION_USE_PROFILE', $dir);
    }
  }

}
