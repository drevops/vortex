<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class DatabaseStoreType extends AbstractHandler {

  public function discover(): null|string|bool|iterable {
    return $this->discoverValueDatabaseImage() ? 'container_image' : 'file';
  }


  public function process(): void {
    $image = $this->getAnswer('database_image');
    File::fileReplaceContent('/VORTEX_DB_IMAGE=.*/', 'VORTEX_DB_IMAGE=' . $image, $this->tmpDir . '/.env');

    if ($image !== '' && $image !== '0') {
      File::removeTokenWithContent('!VORTEX_DB_IMAGE', $this->tmpDir);
    }
    else {
      File::removeTokenWithContent('VORTEX_DB_IMAGE', $this->tmpDir);
    }
  }



}
