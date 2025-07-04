<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class ProvisionType extends AbstractHandler {

  const DATABASE = 'database';

  const PROFILE = 'profile';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $type = Env::getFromDotenv('VORTEX_PROVISION_TYPE', $this->dstDir);

    return $type && in_array($type, [self::DATABASE, self::PROFILE]) ? $type : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    if ($v === static::PROFILE) {
      File::replaceContentInFile(
        $this->tmpDir . '/.env', '/VORTEX_PROVISION_TYPE=.*/',
        "VORTEX_PROVISION_TYPE=" . static::PROFILE
      );

      File::removeTokenAsync('!PROVISION_TYPE_PROFILE');
    }
    else {
      File::replaceContentInFile(
        $this->tmpDir . '/.env', '/VORTEX_PROVISION_TYPE=.*/',
        "VORTEX_PROVISION_TYPE=" . static::DATABASE
      );

      File::removeTokenAsync('PROVISION_TYPE_PROFILE');
    }
  }

}
