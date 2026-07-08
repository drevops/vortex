<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "version_scheme" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class VersionScheme extends AbstractFieldHandler implements OptionsInterface {

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
  public static function id(): string {
    return 'version_scheme';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Release versioning scheme';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Select;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'CalVer (year.month.patch) or SemVer (major.minor.patch).';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return self::CALVER;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 220;
  }

}
