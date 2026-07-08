<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "deploy_types" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class DeployTypes extends AbstractFieldHandler implements OptionsInterface {

  const ARTIFACT = 'artifact';

  const LAGOON = 'lagoon';

  const WEBHOOK = 'webhook';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $types = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];

    if (!in_array('artifact', $types, TRUE)) {
      File::removeTokenAsync('DEPLOY_TYPES_ARTIFACT');
      File::remove($context->directory . '/.gitignore.deployment');
      File::remove($context->directory . '/.gitignore.artifact');
    }

    if (!in_array('webhook', $types, TRUE)) {
      File::removeTokenAsync('DEPLOY_TYPES_WEBHOOK');
    }

    if (!empty($types)) {
      Env::writeValueDotenv('VORTEX_DEPLOY_TYPES', Converter::toList($types), $context->directory . '/.env');
      File::removeTokenAsync('!DEPLOYMENT');
    }
    else {
      File::remove($context->directory . '/docs/deployment.md');
      File::removeTokenAsync('DEPLOYMENT');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::ARTIFACT => 'Code artifact',
      self::LAGOON => 'Lagoon webhook',
      self::WEBHOOK => 'Custom webhook',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'deploy_types';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Deployment types';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::MultiSelect;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'One or more deployment mechanisms.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return fn (Context $c): array => match ($c->answers['hosting_provider'] ?? NULL) { HostingProvider::LAGOON => [self::LAGOON], HostingProvider::ACQUIA => [self::ARTIFACT], default => [self::WEBHOOK] };
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 170;
  }

}
