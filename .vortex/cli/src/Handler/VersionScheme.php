<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "version_scheme" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class VersionScheme extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $version_scheme = is_string($value) ? $value : '';

    Env::writeValueDotenv('VORTEX_RELEASE_VERSION_SCHEME', $version_scheme, $context->directory . '/.env');

    if ($version_scheme === 'semver') {
      File::removeTokenAsync('!VERSION_RELEASE_SCHEME_SEMVER');
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_CALVER');
    }
    elseif ($version_scheme === 'calver') {
      File::removeTokenAsync('!VERSION_RELEASE_SCHEME_CALVER');
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_SEMVER');
    }
    else {
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_SEMVER');
      File::removeTokenAsync('VERSION_RELEASE_SCHEME_CALVER');
    }
  }

}
