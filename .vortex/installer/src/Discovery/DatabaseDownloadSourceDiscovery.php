<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class DatabaseDownloadSourceDiscovery extends AbstractDiscovery {

  public function discover() {
    return $this->getValueFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');
  }

}
