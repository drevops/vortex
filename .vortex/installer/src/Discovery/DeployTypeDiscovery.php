<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class DeployTypeDiscovery extends AbstractDiscovery {

  public function discover() {
    return Env::getValueFromDstDotenv('VORTEX_DEPLOY_TYPES');
  }

}
