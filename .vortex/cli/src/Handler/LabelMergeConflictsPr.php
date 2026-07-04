<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "label_merge_conflicts_pr" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class LabelMergeConflictsPr extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $preserve = is_scalar($value) ? (string) $value : '';

    if (empty($preserve)) {
      File::remove($context->directory . '/.github/workflows/label-merge-conflict.yml');
    }
  }

}
