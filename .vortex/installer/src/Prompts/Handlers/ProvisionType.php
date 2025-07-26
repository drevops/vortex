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
  public function label(): string {
    return '🦋 Provision type';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Selecting "Profile" will install site from a profile rather than a database dump.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::DATABASE => 'Import from database dump',
      self::PROFILE => 'Install from profile',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::DATABASE;
  }

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
