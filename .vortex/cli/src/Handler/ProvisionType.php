<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "provision_type" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class ProvisionType extends AbstractFieldHandler implements OptionsInterface {

  const DATABASE = 'database';

  const PROFILE = 'profile';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $provision_type = is_string($value) ? $value : '';

    Env::writeValueDotenv('VORTEX_PROVISION_TYPE', $provision_type, $context->directory . '/.env');

    if ($provision_type === 'profile') {
      File::remove($context->directory . '/scripts/sanitize.sql');
      File::removeTokenAsync('!PROVISION_TYPE_PROFILE');
    }
    else {
      File::removeTokenAsync('PROVISION_TYPE_PROFILE');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::DATABASE => 'Import from database dump',
      self::PROFILE => 'Install from profile',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'provision_type';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Provision type';
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
    return 'How the site is provisioned: from a database dump or installed from a profile.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return self::DATABASE;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 150;
  }

}
