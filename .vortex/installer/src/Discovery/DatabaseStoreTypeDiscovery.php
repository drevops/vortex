<?php

namespace DrevOps\Installer\Discovery;

class DatabaseStoreTypeDiscovery extends AbstractDiscovery {

  public function discover() {
    return $this->discoverValueDatabaseImage() ? 'container_image' : 'file';
  }

}
