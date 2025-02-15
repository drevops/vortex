<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;

class DatabaseImageHandler extends AbstractHandler {

  public function discover() {
    return Env::getFromDstDotenv('VORTEX_DB_IMAGE');
  }

}
