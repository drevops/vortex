<?php

namespace DrevOps\Installer\Discovery;

class DatabaseImageDiscovery extends AbstractDiscovery {

  public function discover() {
    return Env::getValueFromDstDotenv('VORTEX_DB_IMAGE');
  }

}
