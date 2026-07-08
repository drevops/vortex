<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "assign_author_pr" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class AssignAuthorPr extends AbstractHandler implements FieldInterface {

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
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->confirm('assign_author_pr', 'Auto-assign the author to their PR?')->description('Helps to keep the PRs organized.')->default(TRUE)->weight(50);
  }

}
