<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

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
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    if ($this->response === static::PROFILE) {
      File::replaceContent($this->tmpDir . '/.env', '/VORTEX_PROVISION_TYPE=.*/', "VORTEX_PROVISION_TYPE=" . static::PROFILE);
      File::removeTokenInDir($this->tmpDir, '!PROVISION_TYPE_PROFILE');
    }
    else {
      File::replaceContent($this->tmpDir . '/.env', '/VORTEX_PROVISION_TYPE=.*/', "VORTEX_PROVISION_TYPE=" . static::DATABASE);
      File::removeTokenInDir($this->tmpDir, 'PROVISION_TYPE_PROFILE');
    }
  }

}
