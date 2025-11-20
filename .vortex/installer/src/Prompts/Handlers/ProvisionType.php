<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Tui;

class ProvisionType extends AbstractHandler {

  const DATABASE = 'database';

  const PROFILE = 'profile';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Provision type';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): string {
    $label1 = Tui::bold('Import from database dump');
    $label2 = Tui::bold('Install from profile');

    return <<<DOC
Provisioning sets up the site in an environment using an already assembled codebase.

    ○ {$label1}
      Provisions the site by importing a database dump
      typically copied from production into lower
      environments.

    ○ {$label2}
      Provisions the site by installing a fresh Drupal
      site from a profile every time an environment is
      created.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select the provision type.';
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
    $t = $this->tmpDir;

    Env::writeValueDotenv('VORTEX_PROVISION_TYPE', $v, $t . '/.env');

    if ($v === static::PROFILE) {
      File::removeTokenAsync('!PROVISION_TYPE_PROFILE');
    }
    else {
      File::removeTokenAsync('PROVISION_TYPE_PROFILE');
    }
  }

}
