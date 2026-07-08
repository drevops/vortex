<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "preserve_docs_project" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class PreserveDocsProject extends AbstractFieldHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $preserve = is_scalar($value) ? (string) $value : '';

    if (!empty($preserve)) {
      File::removeTokenAsync('!DOCS_PROJECT');
    }
    else {
      File::remove($context->directory . '/docs');
      File::removeTokenAsync('DOCS_PROJECT');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'preserve_docs_project';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Preserve project documentation?';
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
    return 'Helps to maintain the project documentation within the repository.';
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
    return 30;
  }

}
