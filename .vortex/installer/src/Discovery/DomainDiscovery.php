<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class DomainDiscovery extends AbstractDiscovery {

  public function discover() {
    return $this->getValueFromDstDotenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN');
  }

}
