<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Utils\Env;

class DatabaseDownloadSourceDiscovery extends AbstractDiscovery {

  public function discover() {
    return Env::getFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');
  }

}
