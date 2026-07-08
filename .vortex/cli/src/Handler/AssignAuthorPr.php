<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "assign_author_pr" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class AssignAuthorPr extends AbstractFieldHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value !== TRUE) {
      File::remove($context->directory . '/.github/workflows/assign-author.yml');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'assign_author_pr';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Auto-assign the author to their PR?';
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
    return 'Helps to keep the PRs organized.';
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
    return 50;
  }

}
