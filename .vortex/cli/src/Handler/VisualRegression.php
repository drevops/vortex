<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "visual_regression" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class VisualRegression extends AbstractFieldHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value !== TRUE) {
      File::remove($context->directory . '/.github/workflows/test-vr.yml');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'visual_regression';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Visual regression testing with Diffy?';
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
    return 'Requires a Diffy account.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 80;
  }

}
