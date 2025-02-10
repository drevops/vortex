<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class ProvisionUseProfileDiscovery extends AbstractDiscovery {

  public function discover() {
    return (bool) $this->getValueFromDstDotenv('VORTEX_PROVISION_USE_PROFILE');
  }

}
