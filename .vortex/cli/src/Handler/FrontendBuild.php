<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;

/**
 * Handler for the "frontend_build" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class FrontendBuild extends AbstractFieldHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if (is_bool($value)) {
      Env::writeValueDotenv('VORTEX_FRONTEND_BUILD_SKIP', $value ? '0' : '1', $context->directory . '/.env');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'frontend_build';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Build front-end assets in the container?';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Confirm;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Disable to build theme assets on the host or as part of deployment.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function when(): ?ConditionInterface {
    return new Condition('theme', eq: Theme::CUSTOM);
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 320;
  }

}
