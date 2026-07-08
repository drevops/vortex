<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "version_scheme" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class VersionScheme extends AbstractHandler implements OptionsInterface, FieldInterface {

  const CALVER = 'calver';

  const SEMVER = 'semver';

  const OTHER = 'other';

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

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::CALVER => 'Calendar Versioning (CalVer)',
      self::SEMVER => 'Semantic Versioning (SemVer)',
      self::OTHER => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->select('version_scheme', 'Release versioning scheme')
      ->description('CalVer (year.month.patch) or SemVer (major.minor.patch).')
      ->default(self::CALVER)
      ->options(self::options())
      ->weight(220);
  }

}
