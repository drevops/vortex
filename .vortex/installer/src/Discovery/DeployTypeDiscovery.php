<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class DeployTypeDiscovery extends AbstractDiscovery {

  public function discover() {
    return $this->getValueFromDstDotenv('VORTEX_DEPLOY_TYPES');
  }

}
