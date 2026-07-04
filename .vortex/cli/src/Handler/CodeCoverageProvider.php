<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "code_coverage_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CodeCoverageProvider extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value === 'codecov') {
      File::removeTokenAsync('!CODE_COVERAGE_PROVIDER_CODECOV');
    }
    else {
      File::removeTokenAsync('CODE_COVERAGE_PROVIDER_CODECOV');
    }
  }

}
