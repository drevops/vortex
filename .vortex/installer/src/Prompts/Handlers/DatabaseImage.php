<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;

class DatabaseImage extends AbstractHandler {

  public static function id(): string {
    return 'database_image';
  }

  public function discover(): ?string {
    return Env::getFromDotenv('VORTEX_DB_IMAGE', $this->dstDir);
  }

  public function process():void  {
    // @todo Implement this.
  }

}
