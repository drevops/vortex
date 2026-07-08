<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "label_merge_conflicts_pr" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class LabelMergeConflictsPr extends AbstractHandler implements FieldInterface {

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
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->confirm('label_merge_conflicts_pr', 'Auto-add a CONFLICT label to a PR when conflicts occur?')->description('Helps to quickly identify PRs that need attention.')->default(TRUE)->weight(40);
  }

}
