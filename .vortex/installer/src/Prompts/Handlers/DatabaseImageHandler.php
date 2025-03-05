<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;

class DatabaseImageHandler extends AbstractHandler {

  public function discover(): ?string {
    return Env::getFromDotenv('VORTEX_DB_IMAGE');
  }

  public function process():void  {
    // @todo Implement this.
  }

}
