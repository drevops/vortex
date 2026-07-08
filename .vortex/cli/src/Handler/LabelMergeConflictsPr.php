<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "label_merge_conflicts_pr" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class LabelMergeConflictsPr extends AbstractFieldHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $preserve = is_scalar($value) ? (string) $value : '';

    if (empty($preserve)) {
      File::remove($context->directory . '/.github/workflows/label-merge-conflict.yml');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'label_merge_conflicts_pr';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Auto-add a CONFLICT label to a PR when conflicts occur?';
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
    return 'Helps to quickly identify PRs that need attention.';
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
  public static function weight(): int {
    return 40;
  }

}
