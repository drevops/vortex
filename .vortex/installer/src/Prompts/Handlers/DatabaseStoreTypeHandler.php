<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class DatabaseStoreTypeHandler extends AbstractHandler {

  public function discover(): ?string {
    return $this->discoverValueDatabaseImage() ? 'container_image' : 'file';
  }


  public function process(): void {
    $image = $this->getAnswer('database_image');
    File::fileReplaceContent('/VORTEX_DB_IMAGE=.*/', 'VORTEX_DB_IMAGE=' . $image, $dir . '/.env');

    if ($image !== '' && $image !== '0') {
      File::removeTokenWithContent('!VORTEX_DB_IMAGE', $dir);
    }
    else {
      File::removeTokenWithContent('VORTEX_DB_IMAGE', $dir);
    }
  }



}
