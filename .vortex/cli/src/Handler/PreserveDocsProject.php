<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "preserve_docs_project" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class PreserveDocsProject extends AbstractHandler implements FieldInterface {

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
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->confirm('preserve_docs_project', 'Preserve project documentation?')->description('Helps to maintain the project documentation within the repository.')->default(TRUE)->weight(30);
  }

}
